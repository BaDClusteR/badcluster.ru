import {useAdminCore} from "../admin/useAdminCore";
import {ChapterRow} from "./types";
import columns from "./columns";
import {Link, useParams} from "react-router";
import {ListDataProvider, ListDataProviderRequestOptions, ListDataResponse, ListState} from "@admin/types";
import {useQuery} from "@tanstack/react-query";
import {Book} from "../Book/types";

export default function Chapters() {
  const {bookId} = useParams<{ bookId: string }>();
  const {List, apiCall, convertListStateToQueryParameters, buildAdminUrl} = useAdminCore();

  const {data: book} = useQuery({
    queryKey: ["book", bookId],
    queryFn: ({signal}) => apiCall("GET", `book/${bookId}`, {}, {signal})
  });

  const dataProvider: ListDataProvider<ChapterRow> = {
    getData: async (state: ListState, options: ListDataProviderRequestOptions) => {
      return await apiCall(
        "GET",
        "chapters",
        Object.assign(
          {bookId},
          convertListStateToQueryParameters(state)
        ),
        {signal: options.signal}
      ) as ListDataResponse<ChapterRow>;
    }
  };


  return <List<ChapterRow>
    name={`books/${bookId}/chapters`}
    columns={columns}
    dataProvider={dataProvider}
    labels={{
      title: <><Link to={buildAdminUrl("books")}>Библиотека</Link> :: {
        (book?.title)
          ? <Link to={buildAdminUrl(`books/${bookId}`)}>
            {(book as Book).title}
          </Link>
          : "..."
      } :: Главы</>,
      add: "Новая глава",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные главы ({{count}})?",
        single: row => <>Точно удалить <strong>{row.title}</strong>?</>
      }
    }}
    webPath={`books/${bookId}/chapters`}
    apiEndpoint="chapters"
  />;
}
