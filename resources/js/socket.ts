import { io } from "socket.io-client";

export const socket = io(import.meta.env.VITE_APP_CHATBOT_URL, {
  transports: ["websocket"],
});
