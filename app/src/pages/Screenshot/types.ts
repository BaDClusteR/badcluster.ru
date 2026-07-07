import type {MediaData} from "@/components/EntityForm/fields/mediaBlock/types";

export interface Screenshot {
  image: MediaData | null;
  alt: string;
  position: number | string;
}