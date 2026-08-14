"""
Converts the villa FBX into a web-ready Draco-compressed GLB.

Two things differ from the corridor script:

1. **Per-object decimation.** The interior model was ruined by joining 2,407
   objects into one mesh and decimating that — collapse-decimate keeps large
   flat surfaces and deletes small detailed ones, so all the furniture went.
   Here each object is decimated on its own, and objects below FLOOR_TRIS are
   left untouched, so small props survive at full detail.

2. **Texture-aware materials.** If the Maps/ textures are present the source
   materials are kept as-is. If they are missing, every material is replaced
   with a neutral architectural clay so the model reads as a deliberate white
   massing render rather than an accident.

Run:
    /Applications/Blender.app/Contents/MacOS/Blender --background \
        --python tools/villa-to-glb.py -- <input.fbx> <output.glb> [target_tris] [tex_size] [maps_dir]
"""

import sys
import os
import bpy

argv = sys.argv[sys.argv.index("--") + 1:] if "--" in sys.argv else []

if len(argv) < 2:
    print("usage: villa-to-glb.py -- <input.fbx> <output.glb> [target_tris]")
    sys.exit(1)

src = os.path.abspath(argv[0])
dst = os.path.abspath(argv[1])
target_tris = int(argv[2]) if len(argv) > 2 else 400_000
tex_size = int(argv[3]) if len(argv) > 3 else 1024

# Where the extracted Maps/ live. Defaults to a maps/ folder beside the FBX.
maps_dir = os.path.abspath(argv[4]) if len(argv) > 4 else os.path.join(os.path.dirname(src), "maps")

# Objects smaller than this keep every triangle — they are the detail that
# makes the scene read as real.
FLOOR_TRIS = 800

print(f"[awaqi] source: {src}")

bpy.ops.wm.read_factory_settings(use_empty=True)
bpy.ops.import_scene.fbx(filepath=src)

meshes = [o for o in bpy.context.scene.objects if o.type == "MESH"]
if not meshes:
    print("[awaqi] ERROR: no mesh data imported")
    sys.exit(1)


def tri_count(obj):
    obj.data.calc_loop_triangles()
    return len(obj.data.loop_triangles)


total = sum(tri_count(o) for o in meshes)
print(f"[awaqi] imported {len(meshes)} objects, {total:,} triangles")

# --- textures -------------------------------------------------------------

# The FBX stores absolute paths from the original artist's machine, so point
# Blender at our own copy of the maps before giving up on them.
if maps_dir and os.path.isdir(maps_dir):
    print(f"[awaqi] resolving textures against {maps_dir}")
    try:
        bpy.ops.file.find_missing_files(directory=maps_dir)
    except RuntimeError as err:
        print(f"[awaqi] find_missing_files failed: {err}")

def resolves(img):
    """
    Whether an image actually points at a file on disk.

    Note: `img.has_data` is NOT this test. Blender lazy-loads image pixels, so
    has_data stays False for a perfectly valid texture until something reads
    it — checking it here reports every texture as missing.
    """
    if not img.filepath:
        return False
    return os.path.isfile(bpy.path.abspath(img.filepath))


images = [i for i in bpy.data.images if i.source == "FILE"]
missing = [i for i in images if not resolves(i)]
print(f"[awaqi] textures: {len(images)} referenced, {len(missing)} unresolved")
for img in missing[:5]:
    print(f"[awaqi]   unresolved: {img.name} -> {img.filepath!r}")

if images and len(missing) == len(images):
    print("[awaqi] no textures resolved -> applying architectural clay")

    clay = bpy.data.materials.new("Villa_Clay")
    clay.use_nodes = True
    bsdf = next(n for n in clay.node_tree.nodes if n.type == "BSDF_PRINCIPLED")
    bsdf.inputs["Base Color"].default_value = (0.82, 0.81, 0.79, 1.0)
    bsdf.inputs["Roughness"].default_value = 0.62
    bsdf.inputs["Metallic"].default_value = 0.0

    for obj in meshes:
        obj.data.materials.clear()
        obj.data.materials.append(clay)
else:
    # Keep only the texture feeding Base Color. The pack ships gloss, reflect,
    # bump and displacement maps per material; glTF cannot use most of them,
    # and at ~3 MB each they would dominate the download for no visual gain.
    kept, dropped = set(), 0

    for mat in bpy.data.materials:
        if not mat.use_nodes:
            continue

        bsdf = next((n for n in mat.node_tree.nodes if n.type == "BSDF_PRINCIPLED"), None)
        base_img = None

        if bsdf:
            link = next((l for l in mat.node_tree.links
                         if l.to_node == bsdf and l.to_socket.name == "Base Color"), None)
            if link and link.from_node.type == "TEX_IMAGE":
                base_img = link.from_node.image

        for node in [n for n in mat.node_tree.nodes if n.type == "TEX_IMAGE"]:
            if base_img is not None and node.image == base_img:
                kept.add(node.image.name)
            else:
                mat.node_tree.nodes.remove(node)
                dropped += 1

    print(f"[awaqi] kept {len(kept)} base-colour maps, dropped {dropped} texture nodes")

    # Downscale what survives. Architectural packs ship 2K-3K maps; on a web
    # canvas a 1K map is indistinguishable and roughly 9x smaller.
    scaled = 0
    for img in bpy.data.images:
        if img.name not in kept or not resolves(img):
            continue
        w, h = img.size
        if max(w, h) > tex_size:
            factor = tex_size / max(w, h)
            img.scale(max(int(w * factor), 1), max(int(h * factor), 1))
            scaled += 1

    print(f"[awaqi] downscaled {scaled} maps to max {tex_size}px")

# --- decimation -----------------------------------------------------------

if total > target_tris:
    # Budget is spread proportionally, but only across objects big enough to
    # be worth reducing. Small props are exempt and keep their triangles.
    big = [o for o in meshes if tri_count(o) > FLOOR_TRIS]
    small_total = sum(tri_count(o) for o in meshes if tri_count(o) <= FLOOR_TRIS)
    big_total = sum(tri_count(o) for o in big)
    budget = max(target_tris - small_total, big_total // 20)
    ratio = min(1.0, budget / big_total) if big_total else 1.0

    print(f"[awaqi] {len(big)} objects above the {FLOOR_TRIS}-tri floor")
    print(f"[awaqi] decimating those to {ratio:.3f} ({small_total:,} tris exempt)")

    for obj in big:
        mod = obj.modifiers.new(name="decimate", type="DECIMATE")
        mod.decimate_type = "COLLAPSE"
        mod.ratio = ratio
        bpy.context.view_layer.objects.active = obj
        bpy.ops.object.modifier_apply(modifier=mod.name)

    after = sum(tri_count(o) for o in meshes)
    print(f"[awaqi] result: {after:,} triangles")

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
