declare module 'jsqr' {
  export interface QRCode {
    data: string;
    binaryData: Uint8ClampedArray;
    location: any;
  }

  export default function jsQR(
    data: Uint8ClampedArray,
    width: number,
    height: number,
    options?: any
  ): QRCode | null;
}
