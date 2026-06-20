"""Generate crisp PNG/ICO favicons without external SVG renderers."""
from math import cos, sin, radians
from pathlib import Path

from PIL import Image, ImageDraw

root = Path(__file__).resolve().parents[1]
icons_dir = root / 'public' / 'icons'


def lerp(a, b, t):
    return int(a + (b - a) * t)


def draw_favicon(size):
    img = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    radius = size * 0.22
    cx = cy = size / 2.0

    for t in (0.0, 1.0):
        c = lerp(17, 15, t), lerp(24, 39, t), lerp(39, 42, t)
        draw.rounded_rectangle((0, 0, size - 1, size - 1), radius=int(size * 0.22), fill=(*c, 255))

    glow = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    glow_draw = ImageDraw.Draw(glow)
    glow_draw.ellipse(
        (cx - radius * 1.35, cy - radius * 1.45, cx + radius * 1.35, cy + radius * 0.95),
        fill=(255, 77, 45, int(size * 0.08)),
    )
    img = Image.alpha_composite(img, glow)
    draw = ImageDraw.Draw(img)

    accent = (255, 77, 45, 255)
    accent_soft = (255, 85, 51, 255)

    def point(angle_deg, dist):
        angle = radians(angle_deg - 90)
        return cx + dist * cos(angle), cy + dist * sin(angle)

    outer_r = size * 0.258
    inner_r = size * 0.141
    outer_pts = [point(i * 45, outer_r) for i in range(8)]
    inner_pts = [point(i * 60, inner_r) for i in range(6)]

    ring_w = max(1, int(size * 0.006))
    line_w = max(1, int(size * 0.0065))

    draw.ellipse(
        (cx - outer_r, cy - outer_r, cx + outer_r, cy + outer_r),
        outline=(255, 77, 45, int(size * 0.18)),
        width=ring_w,
    )
    draw.ellipse(
        (cx - inner_r * 1.05, cy - inner_r * 1.05, cx + inner_r * 1.05, cy + inner_r * 1.05),
        outline=(255, 77, 45, int(size * 0.14)),
        width=max(1, ring_w - 1),
    )

    for pt in outer_pts:
        draw.line((cx, cy, pt[0], pt[1]), fill=(255, 77, 45, int(size * 0.34)), width=line_w)
    for i in range(8):
        a, b = outer_pts[i], outer_pts[(i + 1) % 8]
        draw.line((a[0], a[1], b[0], b[1]), fill=(255, 77, 45, int(size * 0.28)), width=max(1, line_w - 1))
    for pt in inner_pts:
        draw.line((cx, cy, pt[0], pt[1]), fill=(255, 77, 45, int(size * 0.38)), width=line_w)
    for i in range(6):
        a, b = inner_pts[i], inner_pts[(i + 1) % 6]
        draw.line((a[0], a[1], b[0], b[1]), fill=(255, 77, 45, int(size * 0.32)), width=max(1, line_w - 1))

    def dot(pt, r, color):
        x, y = pt
        draw.ellipse((x - r, y - r, x + r, y + r), fill=color)

    mini_r = max(2, int(size * 0.017))
    outer_dot_r = max(3, int(size * 0.027))
    core_r = max(4, int(size * 0.047))

    for pt in inner_pts:
        dot(pt, mini_r, accent_soft)
    for pt in outer_pts:
        dot(pt, outer_dot_r, accent)
    dot((cx, cy), core_r, accent)

    return img


sizes = {
    'favicon-16.png': 16,
    'favicon-32.png': 32,
    'apple-touch-icon.png': 180,
    'icon-192.png': 192,
    'icon-512.png': 512,
}

icons_dir.mkdir(parents=True, exist_ok=True)
images = []
for name, size in sorted(sizes.items(), key=lambda item: item[1]):
    image = draw_favicon(size)
    path = icons_dir / name
    image.save(path, optimize=True)
    images.append(image)
    print('wrote', path, size)

ico_path = icons_dir / 'favicon.ico'
images[0].save(
    ico_path,
    format='ICO',
    sizes=[(img.width, img.height) for img in images],
    append_images=images[1:],
)
print('wrote', ico_path)
