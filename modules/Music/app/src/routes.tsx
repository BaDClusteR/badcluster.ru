import {Routes, Route} from "react-router";
import Albums from "./Albums";
import Album from "./Album";
import Tracks from "./Tracks";
import Track from "./Track";

export function MusicRoutes() {
  return (
    <Routes>
      <Route index element={<Albums/>}/>
      <Route path=":albumId/tracks" element={<Tracks/>}/>
      <Route path=":albumId/tracks/new" element={<Track key="track-create"/>}/>
      <Route path=":albumId/tracks/:id" element={<Track key="track-edit"/>}/>
      <Route path="new" element={<Album key="album-create"/>}/>
      <Route path=":id" element={<Album key="album-edit"/>}/>
    </Routes>
  );
}
