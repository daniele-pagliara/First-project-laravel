<script lang="ts">
import { z } from "zod"
import DraggableRow from "./DraggableRow.vue"
import DragHandle from "./DragHandle.vue"
import { ref, h } from 'vue'
import { show } from '@unovis/ts/components/tooltip/style'

export const schema = z.object({
  id: z.number(),
  header: z.string(),
  type: z.string(),
  status: z.string(),
  target: z.string(),
  limit: z.string(),
  reviewer: z.string(),
})
</script>

<script setup lang="ts">
import axios from 'axios'
import { Badge } from '@/components/ui/badge'
import { RestrictToVerticalAxis } from "@dnd-kit/abstract/modifiers"
import {
  IconChevronDown,
  IconChevronLeft,
  IconChevronRight,
  IconChevronsLeft,
  IconChevronsRight,
  IconCircle,
  IconCircleCheckFilled,
  IconDotsVertical,
  IconLayoutColumns,
  IconLoader,
  IconPlus,
  IconX,
  IconCircleFilled,
} from "@tabler/icons-vue"
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
} from "@tanstack/vue-table"
import {
  FlexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from "@tanstack/vue-table"
import { DragDropProvider } from "dnd-kit-vue"

import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'

import { AlertCircleIcon, AlertCircle, CheckCircle2Icon } from 'lucide-vue-next'
import {
  Alert,
  AlertDescription,
  AlertTitle,
} from '@/components/ui/alert'

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog'

const props = defineProps<{
  data: TableData[]
  csrfToken: string

}>()

interface TableData {
  id: number
  header: string
  reviewer: string
  first_name: string // Aggiunto
  last_name: string  // Aggiunto
  email: string
  password?: string
  password_confirmation?: string
  address?: string    // Aggiunto
  phone?: string      // Aggiunto
  type: string
  status: string
  target: string
  limit: string
}

const alertMessage = ref<{ title: string, description: string, variant: 'default' | 'destructive' } | null>(null)

// Funzione comoda per mostrare l'alert e farlo sparire dopo 5 secondi
const showAlert = (title: string, description: string, variant: 'default' | 'destructive' = 'default') => {

  alertMessage.value = { title, description, variant }

  setTimeout(() => {
    alertMessage.value = null
  }, 5000)

}

// Stato per l'AlertDialog
const isAlertDialogOpen = ref(false)
const userAction = ref<{

  id: number,
  name: string,
  type: 'delete' | 'disable' | 'enable'

} | null>(null)

// Funzione per preparare l'apertura dell'alert
const confirmAction = (user: TableData, type: 'delete' | 'disable' | 'enable') => {

  userAction.value = {
    id: user.id,
    name: `${user.first_name} ${user.last_name}`,
    type
  }

  isAlertDialogOpen.value = true
}

// Funzione che esegue l'azione effettiva dopo la conferma
const executeAction = async () => {

  if (!userAction.value) return
  const { id, type } = userAction.value

  isAlertDialogOpen.value = false // Chiude subito il modal

  if (type === 'delete') await deleteUser(id)
  else if (type === 'disable') await disableUser(id)
  else if (type === 'enable') await enableUser(id)
}

const deleteUser = async (id: number) => {
  // AGGIUNGERE CONTROLLO PER NON ELIMINARE SE STESSO
  // EVENTUALMENTE AGGIORNARE CONTROLLO DOPO INSERIMENTO RUOLI
  // if (id === 1) {
  //   showAlert("Non puoi eliminare l'utente amministratore.", "", "destructive");
  //   return;
  // }
  // if (id === props.user.id) {
  //   showAlert("Non puoi eliminare il tuo account mentre sei loggato.", "", "destructive");
  //   return;
  // }
  try {
    await axios.delete(`/users/${id}`, {
      headers: { 'X-CSRF-TOKEN': props.csrfToken }
    });
    showAlert("Utente eliminato con successo.", "", "default");
    window.location.reload(); // Semplice e veloce per ora
  } catch (e) {

    console.error(e);
    showAlert("Errore nell'eliminazione dell'utente.", "", "destructive");
  }
}

const isEditModalOpen = ref(false)
const isViewModalOpen = ref(false)
const isAddModalOpen = ref(false)

const userToEdit = ref<TableData | null>(null)
const isLoading = ref(false)

const newUser = ref<Partial<TableData>>({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  type: '',
  phone: '',
  address: '',
})

const openAddModal = () => {
  newUser.value = {
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    type: '',
    phone: '',
    address: '',
  }

  isAddModalOpen.value = true
}

const editUser = (user: TableData) => {

  userToEdit.value = { ...user }
  isEditModalOpen.value = true

}

const viewUser = (user: TableData) => {

  userToEdit.value = { ...user }
  isViewModalOpen.value = true

}

const disableUser = async (id: number) => {
  try {
    await axios.post(`/users/${id}/disable`, {}, {
      headers: { 'X-CSRF-TOKEN': props.csrfToken }
    });
    setTimeout(() => window.location.reload(), 1000);
    showAlert("Utente disabilitato con successo.", "", "default");
  } catch (e) {
    console.error(e);
    showAlert("Errore nella disabilitazione dell'utente.", "", "destructive");
  }
}

const enableUser = async (id: number) => {
  try {
    await axios.post(`/users/${id}/enable`, {}, {
      headers: { 'X-CSRF-TOKEN': props.csrfToken }
    });
    window.location.reload();
    showAlert("Utente abilitato con successo.", "", "default");
  } catch (e) {
    console.error(e);
    showAlert("Errore nell'abilitazione dell'utente.", "", "destructive");
  }
}

const handleUpdate = async () => {
  if (!userToEdit.value) return

  isLoading.value = true
  try {
    await axios.put(`/users/${userToEdit.value.id}`, {
      name: userToEdit.value.first_name,
      surname: userToEdit.value.last_name,
      email: userToEdit.value.email,
      password: userToEdit.value.password,
      password_confirmation: userToEdit.value.password_confirmation,
      address: userToEdit.value.address,
      phone: userToEdit.value.phone,
    }, {
      headers: { 'X-CSRF-TOKEN': props.csrfToken }
    })

    isEditModalOpen.value = false
    window.location.reload()
  } catch (error) {
    console.error(error)
    showAlert("Errore durante l'aggiornamento dei dati.", "", "destructive")
  } finally {
    isLoading.value = false
  }
}

const handleCreate = async () => {
  if (!newUser.value.first_name || !newUser.value.email || !newUser.value.password) {
    showAlert("Errore", "Per favore compila i campi obbligatori (Nome, Email, Password).", "destructive")
    return
  }

  if (newUser.value.password !== newUser.value.password_confirmation) {
    showAlert("Errore", "Le password non corrispondono.", "destructive")
    return
  }

  isLoading.value = true
  try {
    await axios.post(`/users`, {
      name: newUser.value.first_name,
      surname: newUser.value.last_name,
      email: newUser.value.email,
      password: newUser.value.password,
      password_confirmation: newUser.value.password_confirmation,
      type: newUser.value.type,
      address: newUser.value.address,
      phone: newUser.value.phone,
    }, {
      headers: { 'X-CSRF-TOKEN': props.csrfToken }
    })

    isAddModalOpen.value = false
    showAlert("Successo", "Utente creato con successo.", "default")
    window.location.reload()
  } catch (error) {
    console.error(error)
    showAlert("Errore", "Errore durante la creazione dell'utente.", "destructive")
  } finally {
    isLoading.value = false
  }
}

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})

const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
const currentUserId = currentUser ? Number(currentUser.id) : null;

const columns: ColumnDef<TableData>[] = [
  // {
  //   accessorKey: "id",
  //   // id: "drag",
  //   header: "Id",
  //   cell: ({ row }) => h("div", "#" + row.getValue("id")?.toString() || "-"),
  // },
  {
    accessorKey: "status",
    header: "Stato",
    cell: ({ row }) => {
      const status = row.getValue("status") as string
      return h("div", { class: "flex items-center gap-2" }, [
        status === "Attivo"
          ? h(IconCircleFilled, { class: "h-4 w-4 text-emerald-500" })
          : h(IconCircleFilled, { class: "h-4 w-4 text-yellow-500" }),

      ])
    },
  },
  // {
  //   id: "select",
  //   header: ({ table }) => h(Checkbox, {
  //     "modelValue": table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && "indeterminate"),
  //     "onUpdate:modelValue": value => table.toggleAllPageRowsSelected(!!value),
  //     "aria-label": "Select all",
  //   }),
  //   cell: ({ row }) => h(Checkbox, {
  //     "modelValue": row.getIsSelected(),
  //     "onUpdate:modelValue": value => row.toggleSelected(!!value),
  //     "aria-label": "Select row",
  //   }),
  //   enableSorting: false,
  //   enableHiding: false,
  // },
  {
    accessorKey: "header",
    header: "E-mail",
    cell: ({ row }) => h("div", String(row.getValue("header"))),
    enableHiding: false,
  },
  // {
  //   accessorKey: "type",
  //   header: "Codice Fiscale",
  //   cell: ({ row }) => h("div", String(row.getValue("type"))),
  //   enableHiding: false,
  // },
  {
    accessorKey: "reviewer",
    header: () => h("div", { class: "hidden lg:block" }, "Nome completo"),
    cell: ({ row }) => h("div", { class: "hidden lg:block" }, String(row.getValue("reviewer"))),
    enableHiding: false,
  },
  // {
  //   accessorKey: "status",
  //   header: "Indirizzo",
  //   cell: ({ row }) => h("div", String(row.getValue("status"))),
  //   enableHiding: false,
  // },
  // {
  //   accessorKey: "target",
  //   header: "Telefono",
  //   cell: ({ row }) => h("div", String(row.getValue("target"))),
  //   enableHiding: false,
  // },
  // {
  //   accessorKey: "limit",
  //   header: () => h("div", { class: "flex items-center gap-1" }, [
  //     "Limit",
  //   ]),
  //   cell: ({ row }) => h(Button, {
  //     variant: "ghost",
  //     size: "sm",
  //     class: "h-auto p-1 text-xs font-mono",
  //   }, () => [
  //     h("span", { class: "ml-1 font-semibold" }, String(row.getValue("limit"))),
  //   ]),
  // },
  {
    id: "actions",
    cell: ({ row }) => {

      return h(DropdownMenu, {}, {
        default: () => [
          h(DropdownMenuTrigger, { asChild: true }, {
            default: () => h(Button, {
              variant: "ghost",
              class: "h-8 w-8 p-0",
            }, {
              default: () => [
                h("span", { class: "sr-only" }, "Open menu"),
                h(IconDotsVertical, { class: "h-4 w-4" }),
              ],
            }),
          }),
          h(DropdownMenuContent, { align: "end" }, {
            default: () => [
              h(DropdownMenuItem, {
                onClick: () => editUser(row.original) // Funzione per la modifica
              }, () => "Modifica"),

              h(DropdownMenuItem, {
                onClick: () => viewUser(row.original)
              }, () => "Visualizza"),

              h(DropdownMenuItem, {
                onClick: () => {
                  const action = row.original.status === "Attivo" ? "disable" : "enable"
                  confirmAction(row.original, action)
                }
              }, () => row.original.status === "Attivo" ? "Disabilita" : "Abilita"),

              h(DropdownMenuSeparator, {}),

              h(DropdownMenuItem, {
                class: "text-red-600 focus:text-red-600",
                disabled: row.getValue("id") === currentUserId,
                onClick: () => {
                  if (row.getValue("id") !== currentUserId) {
                    confirmAction(row.original, 'delete')
                  } else {
                    showAlert("Errore", "Non puoi eliminare te stesso!", "destructive")
                  }
                }
              }, () => "Elimina"),
            ],
          }),
        ],
      })
    },
  },
]

const table = useVueTable({
  get data() {
    return props.data
  },
  columns,
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  onSortingChange: (updaterOrValue) => {
    sorting.value = typeof updaterOrValue === "function"
      ? updaterOrValue(sorting.value)
      : updaterOrValue
  },
  onColumnFiltersChange: (updaterOrValue) => {
    columnFilters.value = typeof updaterOrValue === "function"
      ? updaterOrValue(columnFilters.value)
      : updaterOrValue
  },
  onColumnVisibilityChange: (updaterOrValue) => {
    columnVisibility.value = typeof updaterOrValue === "function"
      ? updaterOrValue(columnVisibility.value)
      : updaterOrValue
  },
  onRowSelectionChange: (updaterOrValue) => {
    rowSelection.value = typeof updaterOrValue === "function"
      ? updaterOrValue(rowSelection.value)
      : updaterOrValue
  },
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get columnVisibility() { return columnVisibility.value },
    get rowSelection() { return rowSelection.value },
  },
})
</script>

<template>

  <div v-if="alertMessage" class="mb-4 ml-4 transition-all ease-in-out duration-300">
    <Alert :variant="alertMessage.variant">
      <AlertCircle v-if="alertMessage.variant === 'destructive'" class="h-4 w-4" />
      <CheckCircle2Icon v-else class="h-4 w-4" />
      <AlertTitle>{{ alertMessage.title }}</AlertTitle>
      <AlertDescription>
        {{ alertMessage.description }}
      </AlertDescription>
    </Alert>
  </div>

  <Tabs default-value="outline" class="w-full flex-col justify-start gap-6">
    <div class="flex items-center justify-between px-4 lg:px-6">
      <!-- <Label for="view-selector" class="sr-only">
        View
      </Label>
      <Select default-value="outline">
        <SelectTrigger
          id="view-selector"
          class="flex w-fit @4xl/main:hidden"
          size="sm"
        >
          <SelectValue placeholder="Select a view" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="outline">
            Outline
          </SelectItem>
          <SelectItem value="past-performance">
            Past Performance
          </SelectItem>
          <SelectItem value="key-personnel">
            Key Personnel
          </SelectItem>
          <SelectItem value="focus-documents">
            Focus Documents
          </SelectItem>
        </SelectContent>
      </Select>
      <TabsList class="**:data-[slot=badge]:bg-muted-foreground/30 hidden **:data-[slot=badge]:size-5 **:data-[slot=badge]:rounded-full **:data-[slot=badge]:px-1 @4xl/main:flex">
        <TabsTrigger value="outline">
          Outline
        </TabsTrigger>
        <TabsTrigger value="past-performance">
          Past Performance <Badge variant="secondary">
            3
          </Badge>
        </TabsTrigger>
        <TabsTrigger value="key-personnel">
          Key Personnel <Badge variant="secondary">
            2
          </Badge>
        </TabsTrigger>
        <TabsTrigger value="focus-documents">
          Focus Documents
        </TabsTrigger>
      </TabsList> -->
      <div class="flex items-center gap-2">
        <!-- <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm">
              <IconLayoutColumns />
              <span class="hidden lg:inline">Customize Columns</span>
              <span class="lg:hidden">Columns</span>
              <IconChevronDown />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-56">
            <template v-for="column in table.getAllColumns().filter((column) => typeof column.accessorFn !== 'undefined' && column.getCanHide())" :key="column.id">
              <DropdownMenuCheckboxItem
                class="capitalize"
                :model-value="column.getIsVisible()"
                @update:model-value="(value) => {

                  column.toggleVisibility(!!value)
                }"
              >
                {{ column.id }}
              </DropdownMenuCheckboxItem>
            </template>
</DropdownMenuContent>
</DropdownMenu> -->
        <Button variant="default" size="sm" @click="openAddModal">
          <IconPlus />
          <span class="hidden lg:inline">Nuovo utente</span> <!--AGGIUNGI LOGICA PER MODALE NEW UTENTE-->
        </Button>
      </div>
    </div>
    <TabsContent value="outline" class="relative flex flex-col gap-4 overflow-auto px-4 lg:px-6">
      <div class="overflow-hidden rounded-lg border">
        <DragDropProvider :modifiers="[RestrictToVerticalAxis]">
          <Table>
            <TableHeader class="bg-muted sticky top-0 z-10">
              <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <TableHead v-for="header in headerGroup.headers" :key="header.id" :col-span="header.colSpan">
                  <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                    :props="header.getContext()" />
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody class="**:data-[slot=table-cell]:first:w-8">
              <template v-if="table.getRowModel().rows.length">
                <DraggableRow v-for="row in table.getRowModel().rows" :key="row.id" :row="row" :index="row.index" />
              </template>
              <TableRow v-else>
                <TableCell :col-span="columns.length" class="h-24 text-center">
                  No results.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>


          <!-- Modale per la creazione di un nuovo utente -->
          <Dialog :open="isAddModalOpen" @update:open="isAddModalOpen = $event">
            <DialogContent class="sm:max-w-[500px]">
              <DialogHeader>
                <DialogTitle>Nuovo Utente</DialogTitle>
                <DialogDescription>
                  Inserisci le informazioni personali dell'utente. Clicca su salva per confermare.
                </DialogDescription>
              </DialogHeader>

              <div class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                  <div class="grid gap-2">
                    <Label for="new-name">Nome *</Label>
                    <Input id="new-name" v-model="newUser.first_name" />
                  </div>
                  <div class="grid gap-2">
                    <Label for="new-surname">Cognome</Label>
                    <Input id="new-surname" v-model="newUser.last_name" />
                  </div>
                </div>

                <div class="grid gap-2">
                  <Label for="new-cf">Codice Fiscale</Label>
                  <Input id="new-cf" v-model="newUser.type" />
                </div>

                <div class="grid gap-2">
                  <Label for="new-email">Email *</Label>
                  <Input id="new-email" type="email" v-model="newUser.email" />
                </div>

                <div class="grid gap-2">
                  <Label for="new-password">Password *</Label>
                  <Input id="new-password" type="password" name="password" v-model="newUser.password" />
                </div>

                <div class="grid gap-2">
                  <Label for="new-confirm-password">Conferma Password *</Label>
                  <Input id="new-confirm-password" type="password" name="new-password_confirmation"
                    v-model="newUser.password_confirmation" />
                </div>

                <div class="grid gap-2">
                  <Label for="new-phone">Telefono</Label>
                  <Input id="new-phone" v-model="newUser.phone" placeholder="+39..." />
                </div>

                <div class="grid gap-2">
                  <Label for="new-address">Indirizzo</Label>
                  <Input id="new-address" v-model="newUser.address" />
                </div>
              </div>

              <DialogFooter>
                <Button variant="outline" @click="isAddModalOpen = false">Annulla</Button>
                <Button :disabled="isLoading" @click="handleCreate">
                  <IconLoader v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                  Crea Utente
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>



          <!-- Modale per la modifica dell'utente -->
          <Dialog :open="isEditModalOpen" @update:open="isEditModalOpen = $event">
            <DialogContent class="sm:max-w-[500px]">
              <DialogHeader>
                <DialogTitle>Modifica Profilo Utente</DialogTitle>
                <DialogDescription>
                  Modifica le informazioni personali dell'utente. Clicca su salva per confermare.
                </DialogDescription>
              </DialogHeader>

              <div v-if="userToEdit" class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                  <div class="grid gap-2">
                    <Label for="name">Nome</Label>
                    <Input id="name" v-model="userToEdit.first_name" />
                  </div>
                  <div class="grid gap-2">
                    <Label for="surname">Cognome</Label>
                    <Input id="surname" v-model="userToEdit.last_name" />
                  </div>
                </div>

                <div class="grid gap-2">
                  <Label for="cf">Codice Fiscale</Label>
                  <Input id="cf" v-model="userToEdit.type" />
                </div>

                <div class="grid gap-2">
                  <Label for="email">Email</Label>
                  <Input id="email" type="email" v-model="userToEdit.email" />
                </div>

                <div class="grid gap-2">
                  <Label for="password">Password</Label>
                  <Input id="password" type="password" name="password" v-model="userToEdit.password" />
                </div>

                <div class="grid gap-2">
                  <Label for="confirm-password">Conferma Password</Label>
                  <Input id="password_confirmation" type="password" name="password_confirmation"
                    v-model="userToEdit.password_confirmation" />
                </div>

                <div class="grid gap-2">
                  <Label for="phone">Telefono</Label>
                  <Input id="phone" v-model="userToEdit.phone" placeholder="+39..." />
                </div>

                <div class="grid gap-2">
                  <Label for="address">Indirizzo</Label>
                  <Input id="address" v-model="userToEdit.address" />
                </div>
              </div>

              <DialogFooter>
                <Button variant="outline" @click="isEditModalOpen = false">Annulla</Button>
                <Button :disabled="isLoading" @click="handleUpdate">
                  <IconLoader v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                  Salva modifiche
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <!-- Modale per la visualizzazione dell'utente -->
          <Dialog :open="isViewModalOpen" @update:open="isViewModalOpen = $event">
            <DialogContent class="sm:max-w-[500px]">
              <DialogHeader>
                <DialogTitle>Visualizza Profilo Utente</DialogTitle>
                <DialogDescription>
                  Visualizza le informazioni personali dell'utente.
                </DialogDescription>
              </DialogHeader>

              <div v-if="userToEdit" class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                  <div class="grid gap-2">
                    <Label for="name">Nome</Label>
                    <Input id="name" v-model="userToEdit.first_name" disabled />
                  </div>
                  <div class="grid gap-2">
                    <Label for="surname">Cognome</Label>
                    <Input id="surname" v-model="userToEdit.last_name" disabled />
                  </div>
                </div>

                <div class="grid gap-2">
                  <Label for="cf">Codice Fiscale</Label>
                  <Input id="cf" v-model="userToEdit.type" disabled />
                </div>

                <div class="grid gap-2">
                  <Label for="email">Email</Label>
                  <Input id="email" type="email" v-model="userToEdit.email" disabled />
                </div>

                <div class="grid gap-2">
                  <Label for="password">Password</Label>
                  <Input id="password" type="password" v-model="userToEdit.password" disabled />
                </div>

                <div class="grid gap-2">
                  <Label for="confirm-password">Conferma Password</Label>
                  <Input id="password_confirmation" type="password" name="password_confirmation" disabled />
                </div>

                <div class="grid gap-2">
                  <Label for="phone">Telefono</Label>
                  <Input id="phone" v-model="userToEdit.phone" placeholder="+39..." disabled />
                </div>

                <div class="grid gap-2">
                  <Label for="address">Indirizzo</Label>
                  <Input id="address" v-model="userToEdit.address" disabled />
                </div>
              </div>

              <DialogFooter>
                <!-- <Button variant="outline" @click="isEditModalOpen = false">Annulla</Button>
              <Button :disabled="isLoading" @click="handleUpdate">
                <IconLoader v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                Salva modifiche
              </Button> -->
              </DialogFooter>
            </DialogContent>
          </Dialog>


          <AlertDialog :open="isAlertDialogOpen" @update:open="isAlertDialogOpen = $event">
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>Sei assolutamente sicuro?</AlertDialogTitle>
                <AlertDialogDescription>
                  <template v-if="userAction?.type === 'delete'">
                    Questa azione è irreversibile. Eliminerai definitivamente l'account di <strong>{{ userAction.name
                    }}</strong> e tutti i dati associati.
                  </template>
                  <template v-else-if="userAction?.type === 'disable'">
                    L'utente <strong>{{ userAction.name }}</strong> non potrà più accedere al sistema finché non verrà
                    riabilitato.
                  </template>
                  <template v-else-if="userAction?.type === 'enable'">
                    L'utente <strong>{{ userAction.name }}</strong> riacquisterà immediatamente l'accesso al sistema.
                  </template>
                </AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel>Annulla</AlertDialogCancel>
                <AlertDialogAction :class="userAction?.type === 'delete' ? 'bg-red-600 hover:bg-red-700' : ''"
                  @click="executeAction">
                  Conferma
                </AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>


        </DragDropProvider>
        <DndContext collisionDetection={closestCenter} modifiers={[restrictToVerticalAxis]} onDragEnd={handleDragEnd}
          sensors={sensors} id={sortableId}>
        </DndContext>
      </div>
      <div class="flex items-center justify-between px-4">
        <div class="text-muted-foreground hidden flex-1 text-sm lg:flex">
          {{ table.getFilteredSelectedRowModel().rows.length }} of
          {{ table.getFilteredRowModel().rows.length }} row(s) selected.
        </div>
        <div class="flex w-full items-center gap-8 lg:w-fit">
          <div class="hidden items-center gap-2 lg:flex">
            <Label for="rows-per-page" class="text-sm font-medium">
              Rows per page
            </Label>
            <Select :model-value="table.getState().pagination.pageSize" @update:model-value="(value) => {
              table.setPageSize(Number(value))
            }">
              <SelectTrigger id="rows-per-page" size="sm" class="w-20">
                <SelectValue :placeholder="`${table.getState().pagination.pageSize}`" />
              </SelectTrigger>
              <SelectContent side="top">
                <SelectItem v-for="pageSize in [10, 20, 30, 40, 50]" :key="pageSize" :value="`${pageSize}`">
                  {{ pageSize }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="flex w-fit items-center justify-center text-sm font-medium">
            Page {{ table.getState().pagination.pageIndex + 1 }} of
            {{ table.getPageCount() }}
          </div>
          <div class="ml-auto flex items-center gap-2 lg:ml-0">
            <Button variant="outline" class="hidden h-8 w-8 p-0 lg:flex" :disabled="!table.getCanPreviousPage()"
              @click="table.setPageIndex(0)">
              <span class="sr-only">Go to first page</span>
              <IconChevronsLeft />
            </Button>
            <Button variant="outline" class="size-8" size="icon" :disabled="!table.getCanPreviousPage()"
              @click="table.previousPage()">
              <span class="sr-only">Go to previous page</span>
              <IconChevronLeft />
            </Button>
            <Button variant="outline" class="size-8" size="icon" :disabled="!table.getCanNextPage()"
              @click="table.nextPage()">
              <span class="sr-only">Go to next page</span>
              <IconChevronRight />
            </Button>
            <Button variant="outline" class="hidden size-8 lg:flex" size="icon" :disabled="!table.getCanNextPage()"
              @click="table.setPageIndex(table.getPageCount() - 1)">
              <span class="sr-only">Go to last page</span>
              <IconChevronsRight />
            </Button>
          </div>
        </div>
      </div>
    </TabsContent>
    <TabsContent value="past-performance" class="flex flex-col px-4 lg:px-6">
      <div class="aspect-video w-full flex-1 rounded-lg border border-dashed" />
    </TabsContent>
    <TabsContent value="key-personnel" class="flex flex-col px-4 lg:px-6">
      <div class="aspect-video w-full flex-1 rounded-lg border border-dashed" />
    </TabsContent>
    <TabsContent value="focus-documents" class="flex flex-col px-4 lg:px-6">
      <div class="aspect-video w-full flex-1 rounded-lg border border-dashed" />
    </TabsContent>
  </Tabs>
</template>
