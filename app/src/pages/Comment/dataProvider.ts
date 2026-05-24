import apiCall from "@/utils/apiCall.ts";
import {Comment, CommentContext} from "@/pages/Comment/types.ts";
import type {EntityFormDataProvider} from "@admin/types";

const getDataProvider = (
  id: number,
  setContext: (value: React.SetStateAction<CommentContext | undefined>) => void
): EntityFormDataProvider<Comment> => (
  {
    queryKey: ["comment", id],
    entityId: id,
    getData: async (signal) => {
      const raw = await apiCall("GET", `comment/${id}`, {}, {signal}) as (Comment & CommentContext);
      setContext({
        dateHumanReadable: raw.dateHumanReadable,
        geoIp: raw.geoIp,
        page: raw.page,
        pageLink: raw.pageLink,
        email: raw.email,
        parent: raw.parent,
        name: raw.name || "Аноним"
      });

      return {
        date: raw.date,
        name: raw.name,
        comment: raw.comment,
        status: raw.status
      };
    }
  }
);

export default getDataProvider;
