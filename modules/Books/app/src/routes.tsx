import {Routes, Route} from "react-router";
import Books from "./Books";
import {Book} from "./Book/Book";

export function BlogRoutes() {
  return (
    <Routes>
      <Route index element={<Books/>}/>
      <Route path="new" element={<Book key="post-new"/>}/>
      <Route path=":id" element={<Book key="post-edit"/>}/>
    </Routes>
  );
}
