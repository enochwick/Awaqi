"""
Converts the Sci Fi Corridor OBJ into a web-ready Draco-compressed GLB.

The source ships an .mtl, but it only declares four flat-white materials with
no map_Kd — so a straight import renders the whole corridor white. The material
*names* say what each surface is meant to be, so this script rebuilds them as
proper PBR:

    default             dark hull panelling
    Mat_1               lighter metal trim
    Light_White         emissive white light strips
    Light_Blue          emissive accent strips, tinted to the Awaqi violet
    Yellow_Stripes.tex  the bundled PNG, wired up as a real texture

Geometry is ~273k faces, already inside a sane web budget, so nothing is
decimated by default.

Run:
    /Applications/Blender.app/Contents/MacOS/Blender --background \
        --python tools/corridor-to-glb.py -- <input.obj> <output.glb> [target_tris]
"""

import sys
import os
import bpy

argv = sys.argv[sys.argv.index("--") + 1:] if "--" in sys.argv else []

if len(argv) < 2:
    print("usage: corridor-to-glb.py -- <input.obj> <output.glb> [target_tris]")
    sys.exit(1)

src = os.path.abspath(argv[0])
dst = os.path.abspath(argv[1])
target_tris = int(argv[2]) if len(argv) > 2 else 0  # 0 = keep all geometry
tex_dir = os.path.dirname(src)

# Awaqi brand accent, matching --color-violet in assets/css/main.css.
VIOLET = (0.486, 0.361, 1.0, 1.0)

print(f"[awaqi] source: {src}")

bpy.ops.wm.read_factory_settings(use_empty=True)
bpy.ops.wm.obj_import(filepath=src)

meshes = [o for o in bpy.context.scene.objects if o.type == "MESH"]
if not meshes:
    print("[awaqi] ERROR: no mesh data imported")
    sys.exit(1)

print(f"[awaqi] imported {len(meshes)} objects")

# Merge so the export is a single primitive set per material.
bpy.ops.object.select_all(action="DESELECT")
for obj in meshes:
    obj.select_set(True)
bpy.context.view_layer.objects.active = meshes[0]
bpy.ops.object.join()

merged = bpy.context.view_layer.objects.active
print(f"[awaqi] merged: {len(merged.data.polygons):,} faces")
print(f"[awaqi] material slots: {[s.name for s in merged.material_slots]}")


def principled(mat):
    """Returns the Principled BSDF of a material, creating nodes if needed."""
    mat.use_nodes = True
    for node in mat.node_tree.nodes:
        if node.type == "BSDF_PRINCIPLED":
            return node
    return mat.node_tree.nodes.new("ShaderNodeBsdfPrincipled")


def set_metal(mat, color, roughness, metallic):
    bsdf = principled(mat)
    bsdf.inputs["Base Color"].default_value = color
    bsdf.inputs["Roughness"].default_value = roughness
    bsdf.inputs["Metallic"].default_value = metallic


def set_emissive(mat, color, strength):
    bsdf = principled(mat)
    bsdf.inputs["Base Color"].default_value = color
    bsdf.inputs["Emission Color"].default_value = color
    bsdf.inputs["Emission Strength"].default_value = strength
    bsdf.inputs["Roughness"].default_value = 0.4


def set_textured(mat, path):
    """Wires an image texture into Base Color, plus a soft emissive tint."""
    if not os.path.exists(path):
        print(f"[awaqi] WARNING: texture missing, skipping: {path}")
        return

    bsdf = principled(mat)
    img = mat.node_tree.nodes.new("ShaderNodeTexImage")
    img.image = bpy.data.images.load(path)
    mat.node_tree.links.new(bsdf.inputs["Base Color"], img.outputs["Color"])
    mat.node_tree.links.new(bsdf.inputs["Emission Color"], img.outputs["Color"])
    bsdf.inputs["Emission Strength"].default_value = 0.6
    bsdf.inputs["Roughness"].default_value = 0.5


for slot in merged.material_slots:
    mat = slot.material
    if mat is None:
        continue

    name = mat.name.lower()

    if "light_white" in name:
        set_emissive(mat, (1.0, 0.98, 0.94, 1.0), 5.0)
        print(f"[awaqi]   {mat.name} -> emissive white")
    elif "light_blue" in name:
        set_emissive(mat, VIOLET, 6.0)
        print(f"[awaqi]   {mat.name} -> emissive violet (brand accent)")
    elif "yellow_stripes" in name:
        set_textured(mat, os.path.join(tex_dir, "Yellow_Stripes_tex.png"))
        print(f"[awaqi]   {mat.name} -> textured")
    elif "mat_1" in name:
        set_metal(mat, (0.32, 0.34, 0.40, 1.0), 0.35, 0.85)
        print(f"[awaqi]   {mat.name} -> light metal trim")
    else:
        set_metal(mat, (0.10, 0.11, 0.14, 1.0), 0.45, 0.75)
        print(f"[awaqi]   {mat.name} -> dark hull")

if target_tris:
    tris = len(merged.data.polygons)
    if tris > target_tris:
        ratio = target_tris / tris
        print(f"[awaqi] decimating to {ratio:.4f}...")
        mod = merged.modifiers.new(name="decimate", type="DECIMATE")
        mod.ratio = ratio
        bpy.ops.object.modifier_apply(modifier=mod.name)
        print(f"[awaqi] result: {len(merged.data.polygons):,} faces")

bpy.ops.object.shade_smooth()

print("[awaqi] exporting GLB...")
bpy.ops.export_scene.gltf(
    filepath=dst,
    export_format="GLB",
    export_draco_mesh_compression_enable=True,
    export_draco_mesh_compression_level=6,
    export_apply=True,
    export_yup=True,
    export_cameras=False,
    export_lights=False,
)

print(f"[awaqi] wrote {dst} ({os.path.getsize(dst) / (1024 * 1024):.1f} MB)")
