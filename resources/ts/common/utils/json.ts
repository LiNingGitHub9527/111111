type JSONValue =
  | string
  | number
  | boolean
  | null
  | JSONObject
  | Array<JSONValue>;

export interface JSONObject {
  [key: string]: JSONValue;
}
