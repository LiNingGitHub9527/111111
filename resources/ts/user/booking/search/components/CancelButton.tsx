import React, { CSSProperties, FC } from 'react';

type Props = {
  onClick: (event: React.MouseEvent<HTMLButtonElement>) => void;
};

const CancelButton: FC<Props> = ({ onClick }) => {
  const css: CSSProperties = {
    display: 'block',
    width: '60px',
    height: '28px',
    background: '#fff',
    border: '1px solid #db2828',
    borderRadius: '3px',
    boxSizing: 'border-box',
    color: '#db2828',
    fontWeight: 500,
    fontSize: '13px',
    textAlign: 'center',
    lineHeight: '150%',
    letterSpacing: '0.03em',
  };

  return (
    <button style={css} onClick={onClick}>
      取消
    </button>
  );
};

export default CancelButton;
