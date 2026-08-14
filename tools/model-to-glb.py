"""
Converts a source model (OBJ or FBX) into a web-ready Draco-compressed GLB.

Handles the three things that actually decide whether a model is usable on the
web, in the order they matter:

1. **Textures.** Source materials are kept when their images resolve on disk,
   and downscaled to a sane web size. Note that `image.has_data` is NOT a
   test for a missing file — Blender lazy-loads pixels, so has_data stays False
   for a perfectly valid texture until something reads it.

2. **Polygon count.** Decimation is per object, never on a joined mesh, and
   objects below FLOOR_TRIS are left alone. Collapse-decimating one merged mesh
   preserves large flat surfaces and deletes small detailed ones, which is how
   you lose all the furniture in an interior scene.

3. **Compression.** Draco typically gets 8-9x on mesh data.

Run:
    /Applications/Blender.app/Contents/MacOS/Blender --background \
        --python tools/model-to-glb.py -- <input> <output.glb> [target_tris] [tex_size]

    target_tris  0 keeps all geometry (the default)
    tex_size     longest edge in px, default 1024
"""

import sys
import os
import bpy

argv = sys.argv[sys.argv.index("--") + 1:] if "--" in sys.argv else []

if len(argv) < 2:
    print("usage: model-to-glb.py -- <input> <output.glb> [target_tris] [tex_size]")
    sys.exit(1)

src = os.path.abspath(argv[0])
dst = os.path.abspath(argv[1])
target_tris = int(argv[2]) if len(argv) > 2 else 0
tex_size = int(argv[3]) if len(argv) > 3 else 1024

FLOOR_TRIS = 800

print(f"[awaqi] source: {src}")

bpy.ops.wm.read_factory_settings(use_empty=True)

ext = os.path.splitext(src)[1].lower()
if ext == ".obj":
    bpy.ops.wm.obj_import(filepath=src)
elif ext == ".fbx":
    bpy.ops.import_scene.fbx(filepath=src)
elif ext in (".glb", ".gltf"):
    bpy.ops.import_scene.gltf(filepath=src)
else:
    print(f"[awaqi] ERROR: unsupported input {ext}")
    sys.exit(1)

meshes = [o for o in bpy.context.scene.objects if o.type == "MESH"]
if not meshes:
    print("[awaqi] ERROR: no mesh data imported")
    sys.exit(1)


def tri_count(obj):
    obj.data.calc_loop_triangles()
    return len(obj.data.loop_triangles)


total = sum(tri_count(o) for o in meshes)
print(f"[awaqi] imported {len(meshes)} objects, {total:,} triangles")


def resolves(img):
    if not img.filepath:
        return False
    return os.path.isfile(bpy.path.abspath(img.filepath))


images = [i for i in bpy.data.images if i.source == "FILE"]
ok = [i for i in images if resolves(i)]
print(f"[awaqi] textures: {len(images)} referenced, {len(ok)} resolved")
for img in [i for i in images if not resolves(i)][:5]:
    print(f"[awaqi]   unresolved: {img.name} -> {img.filepath!r}")

scaled = 0
for img in ok:
    w, h = img.size
    if max(w, h) > tex_size:
        f = tex_size / max(w, h)
        img.scale(max(int(w * f), 1), max(int(h * f), 1))
        scaled += 1
print(f"[awaqi] downscaled {scaled} maps to max {tex_size}px")

if target_tris and total > target_tris:
    big = [o for o in meshes if tri_count(o) > FLOOR_TRIS]
    small_total = sum(tri_count(o) for o in meshes if tri_count(o) <= FLOOR_TRIS)
    big_total = sum(tri_count(o) for o in big)
    budget = max(target_tris - small_total, big_total // 20)
    ratio = min(1.0, budget / big_total) if big_total else 1.0

    print(f"[awaqi] decimating {len(big)} objects to {ratio:.3f} "
          f"({small_total:,} tris under the floor are exempt)")

    for obj in big:
        mod = obj.modifiers.new(name="decimate", type="DECIMATE")
        mod.decimate_type = "COLLAPSE"
        mod.ratio = ratio
        bpy.context.view_layer.objects.active = obj
        bpy.ops.object.modifier_apply(modifier=mod.name)

    print(f"[awaqi] result: {sum(tri_count(o) for o in meshes):,} triangles")

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
