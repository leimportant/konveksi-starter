import ListView from './list-view.vue';

export { ListView };
export default ListView;

export interface ListViewProps {
  items: any[];
  keyField?: string;
  displayField?: string;
  isLoading?: boolean;
  emptyMessage?: string;
  showDivider?: boolean;
}

export interface ListViewEmits {
  (e: 'select', item: any): void;
}