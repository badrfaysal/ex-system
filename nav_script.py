import re
with open('nav_block.txt', 'r', encoding='utf-8') as f:
    nav = f.read()

sections = re.findall(r'<div class="mx-2 mb-0.5">.*?</div>\s*</div>', nav, re.DOTALL)
for i, s in enumerate(sections):
    title_match = re.search(r'<span>(.*?)</span>', s)
    if title_match:
        print(f"{i}: {title_match.group(1).strip()}")
