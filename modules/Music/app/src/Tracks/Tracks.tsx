import {useAdminCore} from "../admin/useAdminCore";
import {TrackRow} from "./types";
import columns from "./columns";
import {Link, useParams} from "react-router";
import {ListDataProvider, ListDataProviderRequestOptions, ListDataResponse, ListState} from "@admin/types";
import {useQuery} from "@tanstack/react-query";
import {type Album} from "../Album/types";

export default function Tracks() {
  const {albumId} = useParams<{ albumId: string }>();
  const {List, apiCall, convertListStateToQueryParameters, buildAdminUrl} = useAdminCore();

  const {data: album} = useQuery({
    queryKey: ["album", albumId],
    queryFn: ({signal}) =>
      apiCall("GET", `album/${albumId}`, {}, {signal}) as Promise<Album>
  });

  const dataProvider: ListDataProvider<TrackRow> = {
    getData: async (state: ListState, options: ListDataProviderRequestOptions) => {
      return await apiCall(
        "GET",
        "tracks",
        Object.assign(
          {albumId},
          convertListStateToQueryParameters(state)
        ),
        {signal: options.signal}
      ) as ListDataResponse<TrackRow>;
    }
  };


  return <List<TrackRow>
    name={`music/${albumId}/tracks`}
    columns={columns}
    dataProvider={dataProvider}
    labels={{
      title: <><Link to={buildAdminUrl("music")}>Музыка</Link> :: {
        (album?.title)
          ? <Link to={buildAdminUrl(`music/${albumId}`)}>
            {album.title}
          </Link>
          : "..."
      } :: Треки</>,
      add: "Добавить трек",
      deleteConfirmation: {
        multiple: "Точно удалить выбранные треки ({{count}})?",
        single: row => <>Точно удалить <strong>{row.title}</strong>?</>
      }
    }}
    webPath={`music/${albumId}/tracks`}
    apiEndpoint="tracks"
  />;
}
