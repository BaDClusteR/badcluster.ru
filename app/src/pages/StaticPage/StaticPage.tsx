import {Link, useParams} from "react-router";
import {type StaticPage} from "./types";
import fields from "./fields/fields";
import getDataProvider from "./dataProvider";
import {EntityForm} from "@/components/EntityForm";
import {buildAdminUrl} from "@/utils/buildAdminUrl.ts";

export default function StaticPagePage() {
  const {id} = useParams<{ id: string }>();

  const isCreateMode = !id;

  return (
    <EntityForm<StaticPage>
      fields={fields}
      dataProvider={getDataProvider(id)}
      initialValues={isCreateMode
        ? {
          title: "",
          shortTitle: "",
          content: null,
          slug: "",
          published: false,
          indexable: true,
          textBlock: true,
          publishDate: "",
          metaDescription: "",
          backLinkText: "",
          backLinkUrl: ""
        }
        : undefined}
      webPath="static-pages"
      apiEndpoint="static-page"
      title={() => <>
        <Link to={buildAdminUrl("static-pages")}>Страницы</Link> :: {isCreateMode ? "Новая страница" : `#${id}`}
      </>}
      labels={{
        notFound: {
          text: "Страница не найдена",
          btnCaption: "К списку страниц"
        },
        submit: {
          create: "Создать страницу",
          update: "Сохранить"
        },
        messages: {
          onCreate: "Страница успешно создана",
          onUpdate: "Страница успешно сохранена"
        }
      }}
    />
  );
}
