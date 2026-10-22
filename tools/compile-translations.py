#!/usr/bin/env python3
"""Compile the plugin's gettext PO catalogs into GNU MO files."""

from __future__ import annotations

import argparse
from pathlib import Path

from babel.messages import mofile, pofile


def compile_catalog(po_path: Path) -> Path:
    mo_path = po_path.with_suffix(".mo")

    with po_path.open("rb") as source:
        catalog = pofile.read_po(source, locale=po_path.stem.rsplit("-", 1)[-1])

    with mo_path.open("wb") as destination:
        mofile.write_mo(destination, catalog)

    return mo_path


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Compile PO catalogs in the languages directory to MO files."
    )
    parser.add_argument(
        "catalogs",
        nargs="*",
        type=Path,
        help="PO catalogs to compile (defaults to languages/*.po).",
    )
    arguments = parser.parse_args()

    root = Path(__file__).resolve().parents[1]
    catalogs = arguments.catalogs or sorted((root / "languages").glob("*.po"))

    if not catalogs:
        parser.error("No PO catalogs found.")

    for po_path in catalogs:
        if po_path.suffix != ".po" or not po_path.is_file():
            parser.error(f"Not a PO catalog: {po_path}")

        mo_path = compile_catalog(po_path)
        print(f"Compiled {po_path} -> {mo_path}")


if __name__ == "__main__":
    main()
