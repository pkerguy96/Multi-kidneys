import { useEffect, useRef } from "react";
import { getPusherInstance } from "./pusherInstance";

// The usePusher hook
export const usePusher = (
  channelName: string,
  eventName: string,
  callback: (data: any) => void
): void => {
  const callbackRef = useRef(callback);

  useEffect(() => {
    callbackRef.current = callback;
  }, [callback]);

  useEffect(() => {
    const pusher = getPusherInstance();
    const channel = pusher.subscribe(channelName);

    console.log(`Subscribing to channel: ${channelName}`);
    const wrappedCallback = (data: any) => {
      callbackRef.current(data);
    };
    channel.bind(eventName, wrappedCallback);

    // Cleanup function
    return () => {
      console.log(`Unsubscribing from channel: ${channelName}`);
      channel.unbind(eventName, wrappedCallback);
      channel.unsubscribe();
    };
  }, [channelName, eventName]);
};
