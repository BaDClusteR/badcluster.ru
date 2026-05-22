import {Link, useNavigate, useParams} from "react-router";
import type {EntityCreatedResponse, EntityFormDataProvider} from "@admin/types";
import {CommentDetailed, CommentDetailedContext} from "./types";
import fields from "./fields";
import {API_ENDPOINT, ROOT_ENDPOINT} from "../Comments/Comments";
import apiCall from "@/utils/apiCall";
import {EntityForm} from "@/components/EntityForm";
import {notify} from "@/lib/notify";
import {useState} from "react";

export default function Comment() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const isCreateMode = !id;
  const [context, setContext] = useState<CommentDetailedContext|undefined>(undefined);

  const dataProvider: EntityFormDataProvider<CommentDetailed> | undefined = isCreateMode
    ? undefined
    : {
        queryKey: ['comment', id],
        getData: async (signal) => {
          const raw = await apiCall('GET', `comment/${id}`, {}, { signal }) as (CommentDetailed & CommentDetailedContext);
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
      };

  return (
    <EntityForm<CommentDetailed, CommentDetailedContext>
      fields={fields}
      dataProvider={dataProvider}
      context={context}
      onSubmit={async (values: CommentDetailed) => {
        await apiCall('PUT', `${API_ENDPOINT}/${id}`, values);
        notify.success("Сохранено", "Коммент успешно сохранен");
      }}
      onCreated={(result: EntityCreatedResponse) => {
        if (result?.id) {
          navigate(`${ROOT_ENDPOINT}/${result.id}`, { replace: true });
        }
      }}
      notFoundText="Коммент не найден"
      notFoundBtnCaption="Назад к комментам"
      submitLabel={"Сохранить"}
      title={(_value, context) => <>
        <Link to={ROOT_ENDPOINT}>Комменты</Link> :: {context?.name} ({context?.dateHumanReadable})
      </>}
    />
  );
}
