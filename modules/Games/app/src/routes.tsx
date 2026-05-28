import {Routes, Route} from "react-router";
import Games from "./Games";
import Game from "./Game";
import GameMaterials from "./GameMaterials";
import GameMaterial from "./GameMaterial";

export function BlogRoutes() {
  return (
    <Routes>
      <Route index element={<Games/>}/>
      <Route path="materials" element={<GameMaterials/>}/>
      <Route path="materials/new" element={<GameMaterial key="material-create"/>}/>
      <Route path="materials/:id" element={<GameMaterial key="material-edit"/>}/>
      <Route path="new" element={<Game key="game-create"/>}/>
      <Route path=":id" element={<Game key="game-edit"/>}/>
    </Routes>
  );
}
