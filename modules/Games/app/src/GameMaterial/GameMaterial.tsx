import {useParams} from "react-router";
import {useQuery} from "@tanstack/react-query";
import {useAdminCore} from "../admin/useAdminCore";
import fields from "./fields";
import {type GameMaterial, GameMaterialContext, MaterialGamesCallResult} from "./types";
import {Optional} from "@admin/types";

export default function GameMaterial() {
  const {id} = useParams<{ id: string }>();
  const {EntityForm, apiCall, createEntityFormDataProvider} = useAdminCore();

  const isCreateMode = !id;

  const {data} = useQuery({
    queryKey: ["material_games"],
    queryFn: ({signal}) => apiCall(
      "GET",
      "material_games",
      {},
      {signal}
    )
  });

  const gamesRaw = data as Optional<MaterialGamesCallResult>;

  return (
    <EntityForm<GameMaterial, GameMaterialContext>
      fields={fields}
      dataProvider={createEntityFormDataProvider<GameMaterial>("material", id, isCreateMode)}
      context={{
        games: (gamesRaw as Optional<MaterialGamesCallResult>)?.games ?? []
      }}
      initialValues={
        isCreateMode
          ? {
            type: "F",
            slug: "saves",
            setupInstructions: {
              // @ts-ignore
              blocks: [
                {
                  type: "header",
                  data: {
                    text: "Установка",
                    level: 2,
                    anchor: "setup"
                  }
                }
              ]
            }
          }
          : undefined
      }
      webPath="games/materials"
      apiEndpoint="material"
      labels={{
        notFound: {
          text: "Материал не найден",
          btnCaption: "Назад к материалам"
        },
        submit: {
          create: "Добавить материал",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Материал успешно добавлен",
          onUpdate: "Изменения сохранены"
        }
      }}
    />
  );
}
