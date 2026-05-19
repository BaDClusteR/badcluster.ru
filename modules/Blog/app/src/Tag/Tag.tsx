import { useNavigate, useParams } from "react-router";
import { useAdminCore } from '../admin/useAdminCore';
import type {EntityCreatedResponse, EntityFormDataProvider} from "@admin/types";
import { TagDetailed } from "./types";
import fields from "./fields";
import {API_ENDPOINT, API_ENDPOINT_SINGLE_ENTITY, ROOT_ENDPOINT} from "../Tags/Tags";

export function Tag() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { EntityForm, apiCall, notify } = useAdminCore();

  const isCreateMode = !id;

  const dataProvider: EntityFormDataProvider<TagDetailed> | undefined = isCreateMode
    ? undefined
    : {
        queryKey: ['tag', id],
        getData: async (signal) => {
          return await apiCall('GET', `${API_ENDPOINT_SINGLE_ENTITY}/${id}`, {}, { signal }) as TagDetailed;
        }
      };

  return (
    <EntityForm
      fields={fields}
      dataProvider={dataProvider}
      onSubmit={async (values: TagDetailed) => {
        if (isCreateMode) {
          const result = await apiCall('POST', API_ENDPOINT, values);
          notify.success("Создано", "Тэг успешно создан");
          return result;
        } else {
          await apiCall('PUT', `${API_ENDPOINT}/${id}`, values);
          notify.success("Сохранено", "Тэг успешно сохранен");
        }
      }}
      onCreated={(result: EntityCreatedResponse) => {
        if (result?.id) {
          navigate(`${ROOT_ENDPOINT}/${result.id}`, { replace: true });
        }
      }}
      notFoundText="Тэг не найден"
      notFoundBtnCaption="Назад к тэгам"
      submitLabel={
        isCreateMode
          ? "Создать тэг"
          : "Сохранить"
      }
    />
  );
}
