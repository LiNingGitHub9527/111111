import React, {
  FC,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { KidsPolicy } from '../datatypes';
import { useVh } from '../../../common/hooks/use-vh';
import { PlusSmIcon, XIcon } from '@heroicons/react/outline';
import { MinusCircleIcon, PlusCircleIcon } from '@heroicons/react/solid';

export type RoomPeople = {
  adultNum: number;
  childNums: number[];
};

type Props = {
  isOpen?: boolean;
  initialValue?: RoomPeople[];
  maxAdultNum: number;
  maxChildNum: number;
  maxChildAge: number;
  kidsPolicies: KidsPolicy[];
  close: VoidFunction;
  onChange: (value: RoomPeople[]) => void;
};

const PeopleNumberSelectModal: FC<Props> = ({
  isOpen,
  initialValue,
  maxAdultNum,
  maxChildNum,
  maxChildAge,
  kidsPolicies,
  close,
  onChange,
}) => {
  const roomPeopleDefault = useMemo(
    (): RoomPeople => ({
      adultNum: 1,
      childNums: new Array(kidsPolicies.length).fill(0),
    }),
    [kidsPolicies.length]
  );
  const [roomPeoples, setRoomPeoples] = useState<RoomPeople[]>(
    initialValue ?? [roomPeopleDefault]
  );

  const isScrollNeededRef = useRef<boolean>(false);
  const roomPeoplesContainerRef = useRef<HTMLDivElement | null>(null);

  const handleCloseButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      close();
    },
    [close]
  );

  const createRoomDeleteButtonClickHandler = useCallback(
    (roomIndex: number) =>
      (_event?: React.MouseEvent<HTMLButtonElement>): void => {
        setRoomPeoples([
          ...roomPeoples.slice(0, roomIndex),
          ...roomPeoples.slice(roomIndex + 1),
        ]);
      },
    [roomPeoples]
  );

  const createAdultPlusButtonClickHandler = useCallback(
    (roomIndex: number) =>
      (_event?: React.MouseEvent<HTMLButtonElement>): void => {
        if (roomPeoples[roomIndex].adultNum >= maxAdultNum) {
          return;
        }

        setRoomPeoples([
          ...roomPeoples.slice(0, roomIndex),
          {
            ...roomPeoples[roomIndex],
            adultNum: roomPeoples[roomIndex].adultNum + 1,
          },
          ...roomPeoples.slice(roomIndex + 1),
        ]);
      },
    [maxAdultNum, roomPeoples]
  );

  const createAdultMinusButtonClickHandler = useCallback(
    (roomIndex: number) =>
      (_event?: React.MouseEvent<HTMLButtonElement>): void => {
        if (roomPeoples[roomIndex].adultNum <= 1) {
          return;
        }

        setRoomPeoples([
          ...roomPeoples.slice(0, roomIndex),
          {
            ...roomPeoples[roomIndex],
            adultNum: roomPeoples[roomIndex].adultNum - 1,
          },
          ...roomPeoples.slice(roomIndex + 1),
        ]);
      },
    [roomPeoples]
  );

  const createChildPlusButtonClickHandler = useCallback(
    (roomIndex: number, policyIndex: number) =>
      (_event?: React.MouseEvent<HTMLButtonElement>): void => {
        if (roomPeoples[roomIndex].childNums[policyIndex] >= maxChildNum) {
          return;
        }

        const childNums = roomPeoples[roomIndex].childNums;
        setRoomPeoples([
          ...roomPeoples.slice(0, roomIndex),
          {
            ...roomPeoples[roomIndex],
            childNums: [
              ...childNums.slice(0, policyIndex),
              childNums[policyIndex] + 1,
              ...childNums.slice(policyIndex + 1),
            ],
          },
          ...roomPeoples.slice(roomIndex + 1),
        ]);
      },
    [maxChildNum, roomPeoples]
  );

  const createChildMinusButtonClickHandler = useCallback(
    (roomIndex: number, policyIndex: number) =>
      (_event?: React.MouseEvent<HTMLButtonElement>): void => {
        if (roomPeoples[roomIndex].childNums[policyIndex] <= 0) {
          return;
        }

        const childNums = roomPeoples[roomIndex].childNums;
        setRoomPeoples([
          ...roomPeoples.slice(0, roomIndex),
          {
            ...roomPeoples[roomIndex],
            childNums: [
              ...childNums.slice(0, policyIndex),
              childNums[policyIndex] - 1,
              ...childNums.slice(policyIndex + 1),
            ],
          },
          ...roomPeoples.slice(roomIndex + 1),
        ]);
      },
    [roomPeoples]
  );

  const handleRoomPlusButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      isScrollNeededRef.current = true;
      setRoomPeoples([...roomPeoples, { ...roomPeopleDefault }]);
    },
    [roomPeopleDefault, roomPeoples]
  );

  const scrollToRoomPeoplesEnd = useCallback((): void => {
    const roomPeoplesContainer = roomPeoplesContainerRef.current;
    if (!roomPeoplesContainer) {
      return;
    }

    const scrollSize =
      roomPeoplesContainer.scrollHeight - roomPeoplesContainer.clientHeight;
    if (scrollSize > 0) {
      roomPeoplesContainer.scrollTo({
        top: scrollSize,
        behavior: 'smooth',
      });
    }
  }, []);

  useEffect(() => {
    if (isScrollNeededRef.current) {
      isScrollNeededRef.current = false;
      scrollToRoomPeoplesEnd();
    }
  }, [roomPeoples.length, scrollToRoomPeoplesEnd]);

  const handleConfirmButtonClick = useCallback(
    (_event?: React.MouseEvent<HTMLButtonElement>): void => {
      onChange(roomPeoples);
      handleCloseButtonClick();
    },
    [handleCloseButtonClick, onChange, roomPeoples]
  );

  const vh = useVh();

  if (!isOpen) {
    return null;
  }

  return (
    <>
      <div
        className="static-search_panel__wrapper"
        style={{ top: `calc(10px + (${100 * vh}px - 10px) / 2)` }}
      >
        <div className="relative flex items-center justify-center shadow h-14">
          <button
            type="button"
            className="absolute flex items-center justify-center w-12 h-12 left-1 top-1"
            onClick={handleCloseButtonClick}
          >
            <XIcon className="w-7 h-7" />
          </button>
          <div className="text-lg font-medium">ご利用人数</div>
        </div>
        <div
          ref={roomPeoplesContainerRef}
          className="overflow-scroll"
          style={{ maxHeight: `calc(${100 * vh}px - 165px)` }}
        >
          {roomPeoples.map((roomPeople: RoomPeople, roomIndex: number) => (
            <div key={`room-people-${roomIndex + 1}`} className="p-2.5">
              <div className="flex items-baseline justify-start mb-2.5">
                <div className="text-lg text-gray-500">
                  {roomIndex + 1}部屋目
                </div>
                {roomPeoples.length > 1 && (
                  <button
                    type="button"
                    className="text-sm ml-3.5"
                    onClick={createRoomDeleteButtonClickHandler(roomIndex)}
                  >
                    削除
                  </button>
                )}
              </div>
              <div className="flex items-center justify-between">
                <div className="text-base">
                  大人({maxChildAge + 1}歳以上)人数
                </div>
                <div className="flex items-center justify-between w-28">
                  <button
                    type="button"
                    disabled={roomPeople.adultNum <= 1}
                    className="rounded-full"
                    onClick={createAdultMinusButtonClickHandler(roomIndex)}
                  >
                    <MinusCircleIcon
                      className={[
                        'w-8 h-8',
                        roomPeople.adultNum <= 1 && 'cursor-not-allowed',
                      ]
                        .filter(Boolean)
                        .join(' ')}
                      style={{
                        color:
                          roomPeople.adultNum > 1
                            ? 'rgb(33, 133, 208)'
                            : 'rgb(241, 241, 241)',
                      }}
                    />
                  </button>
                  <div className="text-lg">{roomPeople.adultNum}</div>
                  <button
                    type="button"
                    disabled={roomPeople.adultNum >= maxAdultNum}
                    className="rounded-full"
                    onClick={createAdultPlusButtonClickHandler(roomIndex)}
                  >
                    <PlusCircleIcon
                      className={[
                        'w-8 h-8',
                        roomPeople.adultNum >= maxAdultNum &&
                          'cursor-not-allowed',
                      ]
                        .filter(Boolean)
                        .join(' ')}
                      style={{
                        color:
                          roomPeople.adultNum < maxAdultNum
                            ? 'rgb(33, 133, 208)'
                            : 'rgb(241, 241, 241)',
                      }}
                    />
                  </button>
                </div>
              </div>
              {kidsPolicies.map((policy: KidsPolicy, policyIndex: number) => (
                <div
                  key={policy.age_start}
                  className="flex items-center justify-between mt-4"
                >
                  <div className="text-base">
                    子供({`${policy.age_start}~${policy.age_end}`}歳)人数
                  </div>
                  <div className="flex items-center justify-between w-28">
                    <button
                      type="button"
                      disabled={roomPeople.childNums[policyIndex] <= 0}
                      className="rounded-full"
                      onClick={createChildMinusButtonClickHandler(
                        roomIndex,
                        policyIndex
                      )}
                    >
                      <MinusCircleIcon
                        className={[
                          'w-8 h-8',
                          roomPeople.childNums[policyIndex] <= 0 &&
                            'cursor-not-allowed',
                        ]
                          .filter(Boolean)
                          .join(' ')}
                        style={{
                          color:
                            roomPeople.childNums[policyIndex] > 0
                              ? 'rgb(33, 133, 208)'
                              : 'rgb(241, 241, 241)',
                        }}
                      />
                    </button>
                    <div className="text-lg">
                      {roomPeople.childNums[policyIndex]}
                    </div>
                    <button
                      type="button"
                      disabled={
                        roomPeople.childNums[policyIndex] >= maxChildNum
                      }
                      className="rounded-full"
                      onClick={createChildPlusButtonClickHandler(
                        roomIndex,
                        policyIndex
                      )}
                    >
                      <PlusCircleIcon
                        className={[
                          'w-8 h-8',
                          roomPeople.childNums[policyIndex] >= maxChildNum &&
                            'cursor-not-allowed',
                        ]
                          .filter(Boolean)
                          .join(' ')}
                        style={{
                          color:
                            roomPeople.childNums[policyIndex] < maxChildNum
                              ? 'rgb(33, 133, 208)'
                              : 'rgb(241, 241, 241)',
                        }}
                      />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          ))}
        </div>
        <button
          type="button"
          className="flex items-center justify-start w-full border-t border-b border-l-0 border-r-0 border-gray-300 border-dashed p-2.5"
          onClick={handleRoomPlusButtonClick}
        >
          <PlusSmIcon className="w-6 h-6 -mb-1 mr-1.5" />
          <div className="text-lg">部屋を追加</div>
        </button>
        <div className="p-2.5">
          <button
            type="button"
            className="w-full px-3 text-lg font-medium leading-normal text-white rounded py-1.5"
            style={{ backgroundColor: 'rgb(33, 133, 208)' }}
            onClick={handleConfirmButtonClick}
          >
            選択する
          </button>
        </div>
      </div>
      <div className="static-search_panel__filter common_popup_filter" />
    </>
  );
};

export default PeopleNumberSelectModal;
