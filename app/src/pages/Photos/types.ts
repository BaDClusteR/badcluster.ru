import {MediaItem} from "@/components/MediaGrid/MediaCard";

export interface PhotoRow extends MediaItem {
  uploadedAt: string,
  position: number
}