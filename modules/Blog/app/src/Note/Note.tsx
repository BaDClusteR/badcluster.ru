import {useParams} from "react-router";
import {useAdminCore} from "../admin/useAdminCore";
import {Note} from "./types";
import fields from "./fields";

export default function BlogNote() {
  const {id} = useParams<{ id: string }>();
  const {EntityForm, createEntityFormDataProvider} = useAdminCore();

  const isCreateMode = !id;

  return (
    <EntityForm<Note>
      fields={fields}
      dataProvider={createEntityFormDataProvider<Note>("note", id, isCreateMode)}
      initialValues={isCreateMode ? {published: false} : undefined}
      webPath="blog/notes"
      apiEndpoint="note"
      labels={{
        notFound: {
          text: "Заметка не найдена",
          btnCaption: "Назад к заметкам"
        },
        submit: {
          create: "Создать заметку",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Заметка успешно создана",
          onUpdate: "Заметка успешно сохранена"
        }
      }}
    />
  );
}
