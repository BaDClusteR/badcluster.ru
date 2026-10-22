import {useState} from "react";
import {Link, useNavigate, useParams} from "react-router";
import {Group, Skeleton, Stack, Text, Title} from "@mantine/core";
import {IconTrash} from "@tabler/icons-react";
import {useMutation, useQuery, useQueryClient} from "@tanstack/react-query";
import apiCall from "@/utils/apiCall";
import {buildAdminUrl} from "@/utils/buildAdminUrl";
import {notify} from "@/lib/notify";
import Button from "@/components/primitives/Button";
import Modal from "@/components/primitives/Modal";
import LevelBadge from "@/pages/LogRecords/LevelBadge";
import {type LogRecord as LogRecordData} from "./types";
import classes from "./LogRecord.module.css";

/**
 * Запись журнала целиком: сообщение, стектрейс, контекст. Посмотрел, разобрался —
 * удаляешь, и она пропадает из виджета «Требует внимания».
 */
export default function LogRecord() {
  const {id} = useParams<{ id: string }>();
  const recordId = parseInt(id ?? "") || 0;
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [isConfirmDeletion, setIsConfirmDeletion] = useState(false);

  const {data, isPending, isError} = useQuery({
    queryKey: ["log-record", recordId],
    queryFn: async ({signal}) =>
      await apiCall("GET", `log-record/${recordId}`, {}, {signal}) as unknown as LogRecordData,
    retry: false
  });

  const {mutate: deleteRecord, isPending: isDeleting} = useMutation({
    mutationFn: async () => await apiCall("DELETE", "log-records", {rows: [recordId]}),
    onSuccess: async () => {
      await queryClient.invalidateQueries({queryKey: ["log-records"]});
      await queryClient.invalidateQueries({queryKey: ["attention"]});
      notify.success("Удалено", "Запись журнала удалена");
      navigate(buildAdminUrl("logs"));
    }
  });

  const breadcrumb = <Link to={buildAdminUrl("logs")}>Журнал ошибок</Link>;

  if (isError) {
    return <Stack gap="md">
      <Title order={2}>{breadcrumb} :: запись не найдена</Title>
      <Text>Возможно, её уже удалили.</Text>
    </Stack>;
  }

  if (isPending) {
    return <Stack gap="md">
      <Title order={2}>{breadcrumb}</Title>
      <Skeleton height={240}/>
    </Stack>;
  }

  const meta: [string, string][] = [
    ["Впервые", data.firstSeen],
    ...(data.occurrences > 1
      ? [["Последний раз", data.lastSeen], ["Повторов", String(data.occurrences)]] as [string, string][]
      : []),
    ["Где", data.url],
    ["IP", data.ip],
    ["ID запроса", data.requestId]
  ];

  return <>
    <Modal
      opened={isConfirmDeletion}
      onClose={isDeleting ? () => {} : () => setIsConfirmDeletion(false)}
      withCloseButton={!isDeleting}
      title="Действительно удалить?"
    >
      <p>Точно удалить запись журнала?</p>
      <Group justify="flex-end">
        <Button disabled={isDeleting} onClick={() => setIsConfirmDeletion(false)} variant="default">
          Отмена
        </Button>
        <Button loading={isDeleting} onClick={() => deleteRecord()}>
          Удалить
        </Button>
      </Group>
    </Modal>

    <Stack gap="lg" maw={1100}>
      <Group justify="space-between" align="center">
        <Title order={2} className={classes.title}>
          {breadcrumb} :: <LevelBadge level={data.level}/>
        </Title>
        <Button
          variant="default"
          color="red"
          leftSection={<IconTrash size={16}/>}
          onClick={() => setIsConfirmDeletion(true)}
        >
          Удалить
        </Button>
      </Group>

      <p className={classes.message}>{data.message}</p>

      <dl className={classes.meta}>
        {meta.filter(([, value]) => value).map(([label, value]) => <Group key={label} display="contents">
          <dt>{label}</dt>
          <dd>{value}</dd>
        </Group>)}
      </dl>

      {data.trace && <Stack gap="xs">
        <Title order={4}>Стектрейс</Title>
        <pre className={classes.code}>{data.trace}</pre>
      </Stack>}

      {data.context && <Stack gap="xs">
        <Title order={4}>Контекст</Title>
        <pre className={classes.code}>{data.context}</pre>
      </Stack>}
    </Stack>
  </>;
}
