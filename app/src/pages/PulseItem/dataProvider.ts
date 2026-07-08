import apiCall from "@/utils/apiCall";
import type {EntityFormDataProvider} from "@admin/types";
import type {MediaData} from "@/components/EntityForm/fields/mediaBlock/types";
import {PulseItem} from "./types";

interface PulseItemApiCallResult {
  image: MediaData | null;
  tag: string;
  title: string;
  url: string;
  text: string;
  statusTitle: string;
  statusText: string;
  icon: string;
  position: number;
  isTall: boolean;
  isSurfaced: boolean;
}

const getDataProvider = (id: string | undefined): EntityFormDataProvider<PulseItem> | undefined => {
  if (!id) {
    return undefined;
  }

  return {
    queryKey: ["pulse_item", id],
    entityId: parseInt(id) || 0,
    getData: async (signal) => {
      const raw = await apiCall("GET", `pulse_item/${id}`, {}, {signal}) as PulseItemApiCallResult;

      return {
        image: raw.image,
        tag: raw.tag,
        title: raw.title,
        url: raw.url,
        text: raw.text,
        statusTitle: raw.statusTitle,
        statusText: raw.statusText,
        icon: raw.icon,
        position: raw.position,
        isTall: raw.isTall,
        isSurfaced: raw.isSurfaced
      };
    }
  };
};

export default getDataProvider;
