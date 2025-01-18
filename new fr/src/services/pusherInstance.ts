import Pusher, { Channel } from "pusher-js";

let pusher: Pusher | null = null;

export const getPusherInstance = (): Pusher => {
  if (!pusher) {
    console.log("Initializing global Pusher instance...");
    Pusher.logToConsole = true;

    pusher = new Pusher("fb6987ae79582ce3d9a6", {
      cluster: "eu",
      forceTLS: true,
      enabledTransports: ["ws", "wss", "sockjs"],
    });
  }
  return pusher;
};
