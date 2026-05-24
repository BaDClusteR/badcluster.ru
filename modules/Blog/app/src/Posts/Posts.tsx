import {useAdminCore} from "../admin/useAdminCore";
import {PostRow} from "./types";
import getColumns from "./columns";

export default function BlogPosts() {
  const {List, BadgeGreen, BadgeGray} = useAdminCore();

  return <List<PostRow>
    name="posts"
    columns={getColumns(BadgeGreen, BadgeGray)}
    labels={{
      title: "Посты",
      add: "Новый пост",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные посты ({{count}})?",
        single: row => <>Точно удалить <strong>{row.title}</strong>?</>
      }
    }}
    webPath="blog"
    apiEndpoint="posts"
  />;
}
