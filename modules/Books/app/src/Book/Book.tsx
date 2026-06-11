import {Link, useParams} from "react-router";
import {useAdminCore} from "../admin/useAdminCore";
import {type Book, BookContext} from "./types";
import fields from "./fields";
import {useQuery} from "@tanstack/react-query";

export default function Book() {
  const {id} = useParams<{ id: string }>();
  const {EntityForm, buildAdminUrl, createEntityFormDataProvider, apiCall} = useAdminCore();

  const isCreateMode = !id;

  const {data} = useQuery({
    queryKey: ["book_formats"],
    queryFn: ({signal}) => apiCall(
      "GET",
      "book_formats",
      {},
      {signal}
    )
  });

  return (
    <EntityForm<Book, BookContext>
      fields={fields}
      context={(data as BookContext | undefined) ?? {formats: []}}
      initialValues={{
        type: "A"
      }}
      dataProvider={createEntityFormDataProvider<Book>("book", id, isCreateMode)}
      webPath="books"
      apiEndpoint="book"
      labels={{
        notFound: {
          text: "Произведение не найдено",
          btnCaption: "Назад к списку"
        },
        submit: {
          create: "Добавить",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Произведение добавлено",
          onUpdate: "Изменения сохранены"
        }
      }}
      title={(values) => <>
        <Link to={buildAdminUrl("books")}>Библиотека</Link> :: {
        isCreateMode
          ? "Новое произведение"
          : values?.title ?? "[Безымянное произведение]"
      }
      </>}
    />
  );
}
