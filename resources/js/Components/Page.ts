import Component from './Component';
/**
 * The `Page` component
 *
 * @abstract
 */

type PageAttributes = {
  component: string
  locale: string
  props: Record<string, any>
  url: string
  version: string
};

export default class Page extends Component<{}> {
  page: PageAttributes = JSON.parse($('#app').attr('data-page') as string) as PageAttributes;
  title?: string;
}
