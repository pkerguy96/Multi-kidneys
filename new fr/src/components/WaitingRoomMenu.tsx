import * as React from "react";
import Menu from "@mui/material/Menu";
import AlarmIcon from "@mui/icons-material/Alarm";
import {
  Autocomplete,
  Box,
  Button,
  IconButton,
  TextField,
} from "@mui/material";
import CloseOutlinedIcon from "@mui/icons-material/CloseOutlined";
import DeleteIcon from "@mui/icons-material/Delete";
import {
  FetchPatientsWaitingRoom,
  PatientNameWaitingRoom,
  WaitingroomCounter,
  clearPatientCounterApiClient,
  incrementPatientApiClient,
  incrementbyone,
  waitingRoomApiClient,
} from "../services/WaitingroomService";
import {
  CACHE_KEY_PatientsWaitingRoom,
  CACHE_KEY_WAITINGLIST,
  CACHE_KEY_WaitingRoom,
} from "../constants";
import { useQueryClient } from "@tanstack/react-query";
import { useCallback, useEffect, useState } from "react";
import LoadingSpinner from "./LoadingSpinner";
import addGlobal from "../hooks/addGlobal";
import useDebounce from "../hooks/useDebounce";
import getGlobal from "../hooks/getGlobal";
import { useSnackbarStore } from "../zustand/useSnackbarStore";
import { AxiosError } from "axios";
import PatientSearchAutocomplete from "./PatientSearchAutocomplete";
import Pusher from "pusher-js";
import { usePusher } from "../services/usePusher";

function WaitingRoomMenu() {
  const [events, setEvents] = useState<any[]>([]);
  const [height, setHeight] = useState("auto");
  const [selectedPatient, setSelectedPatient] = useState(null);
  const [anchorEl, setAnchorEl] = React.useState<null | HTMLElement>(null);
  const queryClient = useQueryClient();
  const waiting = getGlobal(
    {} as WaitingroomCounter,
    CACHE_KEY_PatientsWaitingRoom,
    waitingRoomApiClient,
    {
      refetchInterval: 10000,
    }
  );
  const AddPatient = addGlobal({} as incrementbyone, incrementPatientApiClient);
  const { showSnackbar } = useSnackbarStore();
  const adjustHeight = useCallback(
    (options: { id: number; name: string }[]) => {
      const newHeight =
        options.length === 0 ? "auto" : 200 + 30 * options.length + "px";
      setHeight(newHeight);
    },
    []
  );

  const resetPatientCounter = useCallback(async () => {
    try {
      const response = await clearPatientCounterApiClient.getone();
      if (response.status >= 200 && response.status < 300) {
        queryClient.invalidateQueries(CACHE_KEY_PatientsWaitingRoom);
        queryClient.invalidateQueries(CACHE_KEY_WAITINGLIST);
      } else {
      }
    } catch (error) {
      console.log(error);
    }
  }, []);

  const open = Boolean(anchorEl);
  const handleClick = useCallback(
    (event: React.MouseEvent<HTMLButtonElement>) => {
      setAnchorEl(event.currentTarget);
    },
    []
  );
  const handleClose = useCallback(() => {
    setAnchorEl(null);
  }, []);
  const handleEvent = (data: any) => {
    console.log("Event received:", data);
    setEvents((prev) => [...prev, data]); // Add the event to the list
  };
  const addPatientToWaitingList = async () => {
    await AddPatient.mutateAsync(
      { patient_id: selectedPatient?.id },
      {
        onSuccess(data: any) {
          waiting.refetch();
          console.log(data?.message);

          showSnackbar(data?.message, "success");
          queryClient.invalidateQueries(CACHE_KEY_WAITINGLIST);
        },
        onError(error: any) {
          const message =
            error instanceof AxiosError
              ? error.response?.data?.message
              : error.message;
          showSnackbar(message, "error");
        },
      }
    );
  };
  usePusher("test-channel", "patient-added", handleEvent);
  return (
    <div>
      <IconButton
        color="inherit"
        id="basic-button"
        aria-controls={open ? "basic-menu" : undefined}
        aria-haspopup="true"
        aria-expanded={open ? "true" : undefined}
        onClick={handleClick}
      >
        <AlarmIcon />
      </IconButton>

      <Menu
        id="basic-menu"
        anchorEl={anchorEl}
        open={open}
        onClose={handleClose}
        MenuListProps={{
          style: {
            width: "400px",
            height: height,
            maxHeight: 470,
            padding: "12px",
            display: "flex",
            flexDirection: "column",
            overflow: "auto",
            gap: "1rem",
          },
          "aria-labelledby": "basic-button",
        }}
      >
        {waiting.isLoading ? (
          <LoadingSpinner />
        ) : (
          <Box className="flex flex-col gap-4">
            <Box tabIndex={-1} className="flex items-center justify-between">
              <span className="font-medium text-md">Nombre patients</span>
              <Box className="flex flex-row gap-2">
                <span className="flex justify-center items-center text-xl text-[#4B918C]">
                  {waiting.data}
                </span>
                {/* <IconButton onClick={handleClose} color="inherit" size="small">
                  <CloseOutlinedIcon />
                </IconButton> */}
              </Box>
            </Box>
            <Box className="flex justify-center items-center w-full gap-8">
              {/*  <Autocomplete
                disablePortal
                options={options}
                getOptionLabel={(option) => option.name}
                sx={{ width: "100%" }}
                loading={isLoadingPatient}
                loadingText={<LoadingSpinner size="2rem" />}
                onInputChange={(event, newInputValue) => {
                  handleSearch(newInputValue);
                }}
                onChange={handlePatientSelect}
                renderInput={(params) => (
                  <TextField {...params} label="Search Patients" />
                )}
              /> */}
              <PatientSearchAutocomplete
                setPatient={setSelectedPatient}
                onOptionsChange={adjustHeight}
                showExternalLabel={false}
                options={[]}
              />
            </Box>
            <Box className="flex flex-wrap items-center justify-end gap-4">
              <Button
                type="submit"
                variant="contained"
                size="small"
                className="rounded-lg !ms-auto"
                onClick={addPatientToWaitingList}
              >
                Ajouter
              </Button>
              <Button
                className="ml-auto mb-2"
                variant="outlined"
                size="small"
                color="error"
                endIcon={<DeleteIcon />}
                onClick={resetPatientCounter}
              >
                Clear
              </Button>
            </Box>
          </Box>
        )}
      </Menu>
    </div>
  );
}
export default React.memo(WaitingRoomMenu);
