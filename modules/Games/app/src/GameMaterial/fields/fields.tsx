import {FieldDef, Nullable, Optional, SelectOptions} from "@admin/types";
import {GameMaterial, GameMaterialContext, MaterialGame} from "../types";
import MaterialContent from "./MaterialContent";
import {Select, Skeleton} from "@mantine/core";

const FIELDS: FieldDef<GameMaterial, GameMaterialContext>[] = [
  {
    type: "group",
    role: "primary",
    span: "full",
    render: (form, options) =>
      <MaterialContent form={form} options={options}/>,
    visible: values => values.type === "F"
  },
  {
    type: "group",
    render: (form, options) => {
      const games: SelectOptions = (options?.context?.games ?? []).map(
        value => (
          {
            value: value.id.toString(),
            label: value.title
          }
        )
      );

      return <Skeleton visible={options.loading}>
        <Select
          label={"Игра"}
          withAsterisk
          searchable
          data={games ?? []}
          value={games?.length ? (form.values?.gameId?.toString() ?? "") : ""}
          onChange={(value: Nullable<string>) => {
            form.setFieldValue(
              "gameId",
              value
                ? parseInt(value) || 0
                : 0
            );
          }}
        />
      </Skeleton>;
    }
  },
  {
    type: "slug",
    name: "slug",
    label: "Слаг",
    required: true,
    url: (slug, values, context) => {
      let selectedGame: Optional<MaterialGame> = undefined;

      if (context?.games) {
        context.games.forEach(
          game => {
            if (game.id === values.gameId) {
              selectedGame = game;
            }
          }
        );
      }

      return selectedGame
        ? `http://bc.local/games/${(selectedGame as MaterialGame).slug}/${slug}`
        : "";
    },
    visible: values => values.type === "F"
  },
  {
    type: "datetime",
    name: "dateAdded",
    label: "Дата добавления",
    required: true,
    visible: values => values.type === "F"
  },
  {
    type: "text",
    name: "shortTitle",
    label: "Краткий заголовок",
    required: true
  },
  {
    type: "textarea",
    name: "annotation",
    label: "Аннотация",
    required: true,
    span: "full"
  },
  {
    type: "select",
    name: "type",
    label: "Тип",
    span: "full",
    options: [
      {value: "F", label: "Файл"},
      {value: "A", label: "Ссылка"}
    ]
  },
  {
    type: "file",
    name: "file",
    label: "Файл",
    span: "full",
    required: true,
    uploadEndpoint: "/admin/api/material_upload",
    visible: values => values.type === "F"
  },
  {
    type: "text",
    name: "url",
    label: "URL",
    span: "full",
    required: true,
    visible: values => values.type === "A"
  }
];

export default FIELDS;
