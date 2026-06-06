import {Link, useParams} from "react-router";
import {useAdminCore} from "../admin/useAdminCore";
import {type Book, BookContext} from "./types";
import fields from "./fields";
import {useQuery} from "@tanstack/react-query";

export function Book() {
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
        type: "A",
        technicalInfo: {
          "genre": "[book_genre]",
          "authors": [
            {
              "firstName": "[Иван]",
              "middleName": "[Иванович]",
              "lastName": "[Иванов]"
            },
            {
              "firstName": "[Петр]",
              "lastName": "[Петров]"
            }
          ],
          "title": "[Моя книга]",
          "lang": "ru",
          "srcLang": "en",
          "translators": [
            {
              "nickname": "[N@g1b2T0R_666]",
              "homePage": "[https://google.com]",
              "email": "[admin@google.com]"
            }
          ],
          "sequence": {
            "name": "[sequence_name]",
            "number": "[42]"
          },
          "documentInfo": {
            "authors": [
              {
                "nickname": "[N@g1b2T0R_666]",
                "homePage": "[https://google.com]",
                "email": "[admin@google.com]"
              }
            ]
          },
          "version": "1.1"
        }
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
        <Link to={buildAdminUrl("books")}>Книги</Link> :: {
        isCreateMode
          ? "Новое произведение"
          : values?.title ?? "[Безымянное произведение]"
      }
      </>}
    />
  );
}
