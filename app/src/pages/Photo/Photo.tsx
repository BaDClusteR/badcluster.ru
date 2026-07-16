import {Link, useParams} from "react-router";
import {useQuery} from "@tanstack/react-query";
import {type Photo, PhotoContext, PhotoTagsApiCallResult} from "./types";
import fields from "./fields";
import {EntityForm} from "@/components/EntityForm";
import {buildAdminUrl} from "@/utils/buildAdminUrl.ts";
import {createEntityFormDataProvider} from "@/utils/createDataProvider";
import apiCall from "@/utils/apiCall";

export default function PhotoPage() {
  const {id} = useParams<{ id: string }>();

  const isCreateMode = !id;

  const {data} = useQuery({
    queryKey: ["photo_tag_options"],
    queryFn: ({signal}) => apiCall(
      "GET",
      "photo_tag_options",
      {},
      {signal}
    )
  });

  const tagsRaw = data as PhotoTagsApiCallResult | undefined;

  const context: PhotoContext = {
    tags: Array.isArray(tagsRaw?.tags)
      ? tagsRaw.tags.map(
        (t) => ({
          value: String(t.id),
          label: t.title
        })
      )
      : []
  };

  return (
    <EntityForm<Photo, PhotoContext>
      fields={fields}
      dataProvider={createEntityFormDataProvider<Photo>("photo", id, isCreateMode)}
      initialValues={isCreateMode ? {image: null, alt: "", position: "", tags: []} : undefined}
      context={context}
      webPath="photos"
      apiEndpoint="photo"
      title={() => <>
        <Link to={buildAdminUrl("photos")}>Фотки</Link> :: {isCreateMode ? "Новая фотка" : `#${id}`}
      </>}
      preprocessValues={(values) => ({
        image: values.image ? {id: values.image.id} : null,
        alt: values.alt,
        position: Number(values.position) || 0,
        tags: (values.tags ?? []).map(Number)
      })}
      labels={{
        notFound: {
          text: "Фотка не найдена",
          btnCaption: "К списку фоток"
        },
        submit: {
          create: "Добавить фотку",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Фотка успешно добавлена",
          onUpdate: "Фотка успешно сохранена"
        }
      }}
    />
  );
}
