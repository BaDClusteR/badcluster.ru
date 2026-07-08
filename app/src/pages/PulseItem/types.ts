import type {MediaData} from "@/components/EntityForm/fields/mediaBlock/types";

export interface PulseItem {
  image: MediaData | null;
  tag: string;
  title: string;
  url: string;
  text: string;
  statusTitle: string;
  statusText: string;
  icon: string;
  position: number | string;
  isTall: boolean;
  isSurfaced: boolean;
}
