"""
Converts the raw interior OBJ into a web-ready Draco-compressed GLB.

The source is a 1.2 GB / 7M-face Cinema 4D export — roughly 50x too heavy for a
browser. This decimates to a target triangle budget and exports a single GLB.

Run:
    /Applications/Blender.app/Contents/MacOS/Blender --background \
        --python tools/obj-to-glb.py -- <input.obj> <output.glb> [target_tris]
"""

import sys
import os
import bpy

# Args after the "--" separator belong to this script, not to Blender.
argv = sys.argv[sys.argv.index("--") + 1:] if "--" in sys.argv else []

if len(argv) < 2:
    print("usage: obj-to-glb.py -- <input.obj> <output.glb> [target_tris]")
    sys.exit(1)

src = os.path.abspath(argv[0])
dst = os.path.abspath(argv[1])
target_tris = int(argv[2]) if len(argv) > 2 else 250_000

print(f"[awaqi] source : {src}")
print(f"[awaqi] target : {target_tris:,} triangles")

# Start from an empty file rather than the default cube/camera/light.
bpy.ops.wm.read_factory_settings(use_empty=True)

print("[awaqi] importing (this is the slow part)...")
bpy.ops.wm.obj_import(filepath=src)

meshes = [o for o in bpy.context.scene.objects if o.type == "MESH"]
if not meshes:
    print("[awaqi] ERROR: no mesh data imported")
    sys.exit(1)

print(f"[awaqi] imported {len(meshes)} objects")

# Merge everything into one object so a single decimate pass covers the scene.
bpy.ops.object.select_all(action="DESELECT")
for obj in meshes:
    obj.select_set(True)
bpy.context.view_layer.objects.active = meshes[0]
bpy.ops.object.join()

merged = bpy.context.view_layer.objects.active
tris = len(merged.data.loop_triangles) or sum(
    max(len(p.vertices) - 2, 0) for p in merged.data.polygons
)
print(f"[awaqi] merged: {tris:,} triangles")

if tris > target_tris:
    ratio = target_tris / tris
    print(f"[awaqi] decimating to {ratio:.4f} of original...")
    mod = merged.modifiers.new(name="decimate", type="DECIMATE")
    mod.decimate_type = "COLLAPSE"
    mod.ratio = ratio
    bpy.ops.object.modifier_apply(modifier=mod.name)
    print(f"[awaqi] result: {len(merged.data.polygons):,} faces")

# Recalculate normals so the decimated surface lights correctly.
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

size_mb = os.path.getsize(dst) / (1024 * 1024)
print(f"[awaqi] wrote {dst} ({size_mb:.1f} MB)")
