from __future__ import annotations

import base64
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
ASSET_ROOT = (ROOT / "docs" / "uml" / "lucid").resolve()


def validated_target(filename: str) -> Path:
    target = (ASSET_ROOT / filename).resolve()
    if target.parent != ASSET_ROOT or target.suffix.lower() != ".png":
        raise SystemExit("Destino no permitido")
    return target


def save_complete(target: Path, encoded: str) -> None:
    data = base64.b64decode(encoded, validate=True)
    if not data.startswith(b"\x89PNG\r\n\x1a\n"):
        raise SystemExit("El contenido no es un PNG válido")

    ASSET_ROOT.mkdir(parents=True, exist_ok=True)
    target.write_bytes(data)
    print(f"Guardado: {target} ({len(data)} bytes)")


def main() -> None:
    if len(sys.argv) == 2:
        target = validated_target(sys.argv[1])
        encoded = sys.stdin.readline().strip()
        if not encoded:
            raise SystemExit("No se recibió contenido")
        save_complete(target, encoded)
        return

    if len(sys.argv) != 6 or sys.argv[1] != "--chunk":
        raise SystemExit(
            "Uso: save_base64_asset.py <archivo> o "
            "save_base64_asset.py --chunk <archivo> <índice> <total> <contenido>"
        )

    target = validated_target(sys.argv[2])
    index = int(sys.argv[3])
    total = int(sys.argv[4])
    chunk = sys.argv[5]
    temporary = target.with_suffix(target.suffix + ".b64")

    ASSET_ROOT.mkdir(parents=True, exist_ok=True)
    if index == 0:
        temporary.write_text("", encoding="ascii")
    with temporary.open("a", encoding="ascii", newline="") as stream:
        stream.write(chunk)

    if index == total - 1:
        save_complete(target, temporary.read_text(encoding="ascii"))
        temporary.unlink()


if __name__ == "__main__":
    main()
