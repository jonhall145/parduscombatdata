import csv
from pathlib import Path
from typing import Optional

# Converts legacy combat CSV export into the current combat_data schema.
# Defender (d_*) stats are preserved; secondary weapon fields that do not exist
# in the legacy export are set to 0/NULL defaults for compatibility.

LEGACY_COLUMNS = [
    "ID",
    "attacker",
    "ship",
    "crits",
    "critsm",
    "hits",
    "hitsm",
    "jams",
    "shots",
    "shotsm",
    "defender",
    "d_crits",
    "d_hits",
    "d_hitsm",
    "d_critsm",
    "d_jams",
    "d_shots",
    "d_shotsm",
    "log_id",
    "log_time",
    "tactics",
    "hit_accuracy",
    "maneuver",
    "weaponry",
    "engineering",
    "evasion",
    "ECM",
    "ECCM",
]

TARGET_COLUMNS = [
    "id",
    "attacker",
    "ship",
    "ship2",
    "defender",
    "logid",
    "tactics",
    "hit_accuracy",
    "maneuver",
    "weaponry",
    "engineering",
    "evasion",
    "ECM",
    "ECCM",
    "crits",
    "critsm",
    "hits",
    "hitsm",
    "shots",
    "shotsm",
    "jams",
    "d_crits",
    "d_critsm",
    "d_hits",
    "d_hitsm",
    "d_shots",
    "d_shotsm",
    "d_jams",
    "crits2",
    "critsm2",
    "hits2",
    "hitsm2",
    "shots2",
    "shotsm2",
    "jams2",
    "submission_time",
]


def _to_int(value: Optional[str], default: int = 0) -> int:
    if value is None:
        return default
    value = value.strip()
    if not value or value.upper() == "NULL":
        return default
    try:
        return int(float(value))
    except ValueError:
        return default


def _to_float(value: Optional[str], default: float = 0.0) -> float:
    if value is None:
        return default
    value = value.strip()
    if not value or value.upper() == "NULL":
        return default
    try:
        return float(value)
    except ValueError:
        return default


def _to_str_or_none(value: Optional[str]) -> Optional[str]:
    if value is None:
        return None
    value = value.strip()
    if not value or value.lower() == "unknown" or value.upper() == "NULL":
        return None
    return value


def convert_legacy_csv(input_path: Path, output_path: Path) -> None:
    with input_path.open(newline="", encoding="utf-8") as src, output_path.open(
        "w", newline="", encoding="utf-8"
    ) as dst:
        reader = csv.DictReader(src)
        missing = [col for col in LEGACY_COLUMNS if col not in reader.fieldnames]
        if missing:
            raise ValueError(f"Input is missing expected columns: {missing}")

        writer = csv.DictWriter(dst, fieldnames=TARGET_COLUMNS)
        writer.writeheader()

        for row in reader:
            output = {
                "id": _to_int(row.get("ID"), default=0),
                "attacker": row.get("attacker", "").strip(),
                "ship": row.get("ship", "").strip(),
                "ship2": None,
                "defender": row.get("defender", "").strip(),
                "logid": row.get("log_id", "").strip(),
                "tactics": _to_float(row.get("tactics")),
                "hit_accuracy": _to_float(row.get("hit_accuracy")),
                "maneuver": _to_float(row.get("maneuver")),
                "weaponry": _to_float(row.get("weaponry")),
                "engineering": _to_float(row.get("engineering")),
                "evasion": _to_float(row.get("evasion")),
                "ECM": _to_str_or_none(row.get("ECM")),
                "ECCM": _to_str_or_none(row.get("ECCM")),
                "crits": _to_int(row.get("crits")),
                "critsm": _to_int(row.get("critsm")),
                "hits": _to_int(row.get("hits")),
                "hitsm": _to_int(row.get("hitsm")),
                "shots": _to_int(row.get("shots")),
                "shotsm": _to_int(row.get("shotsm")),
                "jams": _to_int(row.get("jams")),
                "d_crits": _to_int(row.get("d_crits")),
                "d_critsm": _to_int(row.get("d_critsm")),
                "d_hits": _to_int(row.get("d_hits")),
                "d_hitsm": _to_int(row.get("d_hitsm")),
                "d_shots": _to_int(row.get("d_shots")),
                "d_shotsm": _to_int(row.get("d_shotsm")),
                "d_jams": _to_int(row.get("d_jams")),
                # Secondary weapon stats are not present in the legacy file
                "crits2": 0,
                "critsm2": 0,
                "hits2": 0,
                "hitsm2": 0,
                "shots2": 0,
                "shotsm2": 0,
                "jams2": 0,
                "submission_time": _to_int(row.get("log_time")),
            }
            writer.writerow(output)


def main() -> None:
    input_path = Path("asdwtbdf_parduscombatdata (1).csv")
    # Use a distinct output file name to avoid clobbering an open file
    output_path = Path("converted_combat_data_with_defender.csv")

    convert_legacy_csv(input_path, output_path)
    print(f"Wrote reformatted CSV to {output_path.resolve()}")


if __name__ == "__main__":
    main()
