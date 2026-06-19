from PIL import Image
import numpy as np
from collections import deque
from pathlib import Path

src = Path(
    r'C:\Users\LyhourMao\.cursor\projects\d-Programming-in-University-PHP-Project-Viilages-Connection'
    r'\assets\c__Users_LyhourMao_AppData_Roaming_Cursor_User_workspaceStorage_99d7d6dd5828fbd88c235cd0ebe1427a'
    r'_images_1-d39b082f-9bad-4f83-86bc-b871e15e4d0e.png'
)
out_dir = Path(__file__).resolve().parents[1] / 'public' / 'icons'


def is_bg(rgb):
    r, g, b = [float(x) for x in rgb]
    light = (r + g + b) / 3.0
    mx, mn = max(r, g, b), min(r, g, b)
    sat = 0.0 if mx == 0 else (mx - mn) / mx
    if light > 175 and sat < 0.22:
        return True
    if light > 155 and sat < 0.14:
        return True
    return False


def trim_transparent_edges(image):
    arr = np.array(image)
    alpha = arr[:, :, 3]

    def column_has_content(x):
        col = arr[:, x]
        visible = col[:, 3] > 30
        if visible.sum() < arr.shape[0] * 0.01:
            return False
        rgb = col[visible, :3].astype(np.float32)
        light = rgb.mean(axis=1)
        return (light < 220).sum() >= max(6, int(visible.sum() * 0.04))

    def row_has_content(y):
        row = arr[y, :]
        visible = row[:, 3] > 30
        if visible.sum() < arr.shape[1] * 0.01:
            return False
        rgb = row[visible, :3].astype(np.float32)
        light = rgb.mean(axis=1)
        return (light < 220).sum() >= max(6, int(visible.sum() * 0.04))

    x0 = next((x for x in range(arr.shape[1]) if column_has_content(x)), 0)
    x1 = next((x for x in range(arr.shape[1] - 1, -1, -1) if column_has_content(x)), arr.shape[1] - 1) + 1
    y0 = next((y for y in range(arr.shape[0]) if row_has_content(y)), 0)
    y1 = next((y for y in range(arr.shape[0] - 1, -1, -1) if row_has_content(y)), arr.shape[0] - 1) + 1
    return image.crop((x0, y0, x1, y1))


def letterbox_square(image, size):
    image = image.copy()
    image.thumbnail((size, size), Image.Resampling.LANCZOS)
    canvas = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    offset = ((size - image.size[0]) // 2, (size - image.size[1]) // 2)
    canvas.paste(image, offset, image)
    return canvas


img = Image.open(src).convert('RGBA')
data = np.array(img)
h, w = data.shape[:2]

visited = np.zeros((h, w), dtype=bool)
q = deque()
for y, x in ((0, 0), (0, w - 1), (h - 1, 0), (h - 1, w - 1)):
    visited[y, x] = True
    q.append((y, x))

while q:
    y, x = q.popleft()
    rgb = data[y, x, :3]
    if not is_bg(rgb):
        continue
    data[y, x, 3] = 0
    for dy, dx in ((-1, 0), (1, 0), (0, -1), (0, 1)):
        ny, nx = y + dy, x + dx
        if 0 <= ny < h and 0 <= nx < w and not visited[ny, nx]:
            visited[ny, nx] = True
            q.append((ny, nx))

alpha = data[:, :, 3]
coords = np.argwhere(alpha > 20)
y0, x0 = coords.min(axis=0)
y1, x1 = coords.max(axis=0) + 1
wordmark = trim_transparent_edges(Image.fromarray(data[y0:y1, x0:x1]))

out_dir.mkdir(parents=True, exist_ok=True)
wordmark.save(out_dir / 'logo.png', optimize=True)
wordmark.save(out_dir / 'logo-full.png', optimize=True)

letterbox_square(wordmark, 192).save(out_dir / 'icon-192.png', optimize=True)
letterbox_square(wordmark, 512).save(out_dir / 'icon-512.png', optimize=True)

# Light version for dark navbar (white text, keep red accent readable).
light_data = np.array(wordmark)
rgb = light_data[:, :, :3].astype(np.float32)
alpha_ch = light_data[:, :, 3]
dark_mask = (rgb.mean(axis=2) < 120) & (alpha_ch > 30)
light_data[dark_mask, 0] = 255
light_data[dark_mask, 1] = 255
light_data[dark_mask, 2] = 255
Image.fromarray(light_data).save(out_dir / 'logo-light.png', optimize=True)

print('wordmark', wordmark.size)
