import {Skeleton, Textarea} from "@mantine/core";
import postClasses from "../../Post/fields/PostEditor.module.css";
import classes from "./NoteEditor.module.css";
import placeholders from "../placeholders";
import {FormErrors, UseFormReturnType} from "@mantine/form";
import {Note} from "../types";
import {EntityFormRenderOptions} from "@admin/types";
import React from "react";

export default function NoteEditor(
  {
    form,
    options
  }: {
    form: UseFormReturnType<Note, Note, (values: Note) => FormErrors>,
    options: EntityFormRenderOptions
  }
): React.JSX.Element {
  const {BlocksField, FieldGroup} = options!.components;
  const titleProps = form.getInputProps("title");
  return <>
    <FieldGroup isSubmitting={form.submitting}>
      <Skeleton visible={options?.loading}>
        <Textarea
          autosize
          placeholder="Заголовок заметки"
          {...titleProps}
          classNames={{input: postClasses.postTitle}}
          onKeyDown={
            (e) => {
              if (e.key === "Enter") {
                e.preventDefault();
              }
            }
          }
          onChange={
            (e) => {
              e.target.value = e.target.value.replace(/[\r\n]+/gm, " ");
              titleProps.onChange(e);
            }
          }
        />
        <BlocksField
          className={`${postClasses.contentInline} ${classes.contentInline}`}
          showSettings={false}
          value={form.values.content as never}
          placeholder={placeholders[Math.floor(Math.random() * placeholders.length)]}
          onChange={(data) => {
            form.setFieldValue("content", data as never);
          }}
        />
      </Skeleton>
    </FieldGroup>
  </>;
}
