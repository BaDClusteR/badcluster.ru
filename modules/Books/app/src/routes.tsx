import {Routes, Route} from "react-router";
import Books from "./Books";
import Book from "./Book";
import Chapters from "./Chapters";
import Chapter from "./Chapter";

export function BlogRoutes() {
  return (
    <Routes>
      <Route index element={<Books/>}/>
      <Route path=":bookId/chapters" element={<Chapters/>}/>
      <Route path=":bookId/chapters/new" element={<Chapter key="chapter-new"/>}/>
      <Route path=":bookId/chapters/:id" element={<Chapter key="chapter-edit"/>}/>
      <Route path="new" element={<Book key="book-new"/>}/>
      <Route path=":id" element={<Book key="book-edit"/>}/>
    </Routes>
  );
}
