import {Routes, Route} from "react-router";
import Games from "./Games";
import Game from "./Game";

export function BlogRoutes() {
  return (
    <Routes>
      <Route index element={<Games/>}/>
      <Route path="new" element={<Game key="game-create"/>}/>
      <Route path=":id" element={<Game key="game-edit"/>}/>
    </Routes>
  );
}
