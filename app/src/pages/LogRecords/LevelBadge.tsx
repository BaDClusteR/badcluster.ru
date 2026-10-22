import {BadgeRed, BadgeYellow} from "@/components/primitives/Badge";
import {LogLevel} from "./types";

export default function LevelBadge({level}: { level: LogLevel }) {
  return level === "WARNING"
    ? <BadgeYellow>{level}</BadgeYellow>
    : <BadgeRed>{level}</BadgeRed>;
}
