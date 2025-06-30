declare module '@inertiajs/core' {
  interface PageProps {
    auth: {
      user: {
        id: number
        name: string
        email: string
      } | null
    }
  }

  export interface PageProps {
      component: string;
      props: {
          ziggy: ZiggyConfig;
          [key: string]: any;
      };
      url: string;
      version: string | null;
  }
}
