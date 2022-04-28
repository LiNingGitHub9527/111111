/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
import React, { FC, useCallback, useState } from 'react';
import { KidsPolicy } from '../datatypes';
import PeopleNumberSelectModal, { RoomPeople } from './PeopleNumberSelectModal';
import { ChevronDownIcon } from '@heroicons/react/solid';

type Props = {
  value: RoomPeople[];
  maxAdultNum: number;
  maxChildNum: number;
  maxChildAge: number;
  kidsPolicies: KidsPolicy[];
  onChange: (value: RoomPeople[]) => void;
};

const PeopleNumberInput: FC<Props> = ({
  value,
  kidsPolicies,
  maxAdultNum,
  maxChildNum,
  maxChildAge,
  onChange,
}) => {
  const [isOpen, setIsOpen] = useState<boolean>(false);

  const handleRoomPeopleTextClick = useCallback(
    (_event?: React.MouseEvent<HTMLElement>) => {
      setIsOpen(true);
    },
    []
  );

  const handleClose = useCallback((): void => {
    setIsOpen(false);
  }, []);

  const handleChange = useCallback(
    (roomPeoples: RoomPeople[]): void => {
      onChange(roomPeoples);
    },
    [onChange]
  );

  return (
    <>
      <div className="flex items-center justify-start mb-0.5">
        <div className="mr-2 text-base leading-normal">部屋数</div>
        <div
          className="text-base leading-normal text-gray-900 underline cursor-pointer"
          onClick={handleRoomPeopleTextClick}
        >
          {value.length}部屋
        </div>
        <div className="mt-0.5 ml-0.5">
          <ChevronDownIcon className="w-4 h-4" />
        </div>
      </div>
      {value.map((roomPeople: RoomPeople, roomIndex: number) => (
        <div key={`selected-room-people-${roomIndex + 1}`} className="mb-0.5">
          <div className="mr-2 text-sm text-gray-500">
            {roomIndex + 1}部屋目
          </div>
          <div
            className="text-base leading-normal text-gray-900 underline cursor-pointer"
            onClick={handleRoomPeopleTextClick}
          >
            {[
              `大人(${maxChildAge + 1}歳以上)${roomPeople.adultNum}名様`,
              kidsPolicies
                .map(
                  (policy: KidsPolicy, policyIndex: number) =>
                    roomPeople.childNums[policyIndex] > 0 &&
                    `子供(${policy.age_start}~${policy.age_end}歳)${roomPeople.childNums[policyIndex]}名様`
                )
                .filter(Boolean)
                .join(', '),
            ]
              .filter(Boolean)
              .join(', ')}
          </div>
        </div>
      ))}
      {isOpen && (
        <PeopleNumberSelectModal
          isOpen
          initialValue={value}
          maxAdultNum={maxAdultNum}
          maxChildNum={maxChildNum}
          maxChildAge={maxChildAge}
          kidsPolicies={kidsPolicies}
          close={handleClose}
          onChange={handleChange}
        />
      )}
    </>
  );
};

export default PeopleNumberInput;
