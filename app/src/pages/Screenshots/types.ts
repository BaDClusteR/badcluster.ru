import {MediaItem} from "@/components/MediaGrid/MediaCard";

export interface ScreenshotRow extends MediaItem {
  uploadedAt: string,
  position: number
}
