import type {MediaData} from "@/components/EntityForm/fields/mediaBlock/types";
import type {SelectOption} from "@admin/types";

export interface Photo {
  image: MediaData | null;
  position: number | string;
  tags: string[];
}

export interface PhotoContext {
  tags: SelectOption[];
}

export interface PhotoTagApi {
  id: number;
  title: string;
}

export interface PhotoTagsApiCallResult {
  tags: PhotoTagApi[];
}
