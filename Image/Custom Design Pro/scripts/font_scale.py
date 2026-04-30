import re
from pathlib import Path

BASE = Path(__file__).resolve().parent.parent
TARGETS = [
    BASE / "css" / "cd-pro.css",
    BASE / "css" / "product.css",
    BASE / "css" / "Fill.css",
    BASE / "css" / "Text.css",
    BASE / "css" / "icon.css",
    BASE / "css" / "flag.css",
    BASE / "css" / "shape.css",
    BASE / "Js" / "adddesign.js",
    BASE / "Js" / "upload.js",
]

FONT_PATTERN = re.compile(r"(font-size:\s*)(\d+(?:\.\d+)?)(px)(\s*(?:!important)?)(\s*;)")


def transform_file(path: Path) -> int:
    text = path.read_text(encoding="utf-8")

    def _replace(match: re.Match[str]) -> str:
        prefix, value, _, important, suffix = match.groups()
        if value == "0":
            return match.group(0)
        return f"{prefix}calc(var(--cdp-font-scale, 1) * {value}px){important}{suffix}"

    new_text, count = FONT_PATTERN.subn(_replace, text)
    if count:
        path.write_text(new_text, encoding="utf-8")
    return count


def main() -> None:
    total = 0
    for target in TARGETS:
        if not target.exists():
            print(f"Skipping missing file: {target}")
            continue
        count = transform_file(target)
        total += count
        if count:
            print(f"Updated {target} ({count} replacements)")
        else:
            print(f"No changes for {target}")
    print(f"Done. Total replacements: {total}")


if __name__ == "__main__":
    main()
