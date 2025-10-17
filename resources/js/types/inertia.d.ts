declare module '@inertiajs/core' {
  interface PageProps {
    auth: {
      user: {
        id: number
        name: string
        phone_number: string
        address: string
        email: string
        location_id: number | null
      } | null
    }
  }

  export interface PageProps {
      component: string;
      props: {
          // ziggy: ZiggyConfig;
          [key: string]: any;
      };
      url: string;
      version: string | null;
  }
}
