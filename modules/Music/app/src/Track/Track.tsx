import {Link, useParams} from "react-router";
import {useAdminCore} from "../admin/useAdminCore";
import fields from "./fields";
import {type Track, TrackContext} from "./types";
import {useQuery} from "@tanstack/react-query";

export default function Track() {
  const {id, albumId} = useParams<{ id: string, albumId: string }>();
  const {EntityForm, createEntityFormDataProvider, apiCall, buildAdminUrl} = useAdminCore();

  const {data: track} = useQuery({
    queryKey: ["album", albumId],
    queryFn: ({signal}) =>
      apiCall("GET", `album/${albumId}`, {}, {signal})
  });

  const context: TrackContext = {
    albumName: String(track?.title || "...")
  };

  return (
    <EntityForm<Track, TrackContext>
      fields={fields}
      dataProvider={createEntityFormDataProvider<Track>("track", id, !id)}
      webPath={`music/${albumId}/tracks`}
      initialValues={{
        albumId: Number(albumId || 0)
      }}
      context={context}
      apiEndpoint="track"
      labels={{
        notFound: {
          text: "Трек не найден",
          btnCaption: "Назад к трекам"
        },
        submit: {
          create: "Добавить трек",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Трек успешно добавлен",
          onUpdate: "Изменения сохранены"
        }
      }}
      title={(data, context) => (
        <>
          <Link to={buildAdminUrl("music")}>Музыка</Link> ::&nbsp;
          {
            track
              ? <Link to={buildAdminUrl(`music/${albumId}/tracks`)}>{context?.albumName}</Link>
              : context?.albumName
          } ::&nbsp;
          {
            id
              ? data?.title
              : "Новый трек"
          }
        </>
      )}
    />
  );
}
