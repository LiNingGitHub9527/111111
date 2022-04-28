import axios, { AxiosInstance, AxiosResponse } from 'axios';
import { JSONObject } from './json';

const invokeRequest = async (
  request: () => Promise<AxiosResponse>
): Promise<unknown> => {
  const response = await request();

  return response.data;
};

class API {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
  }

  async get(path: string, params?: JSONObject): Promise<unknown> {
    const request = (): Promise<AxiosResponse> =>
      this.client.get(path, { params });

    return await invokeRequest(request);
  }

  async delete(path: string, params?: JSONObject): Promise<unknown> {
    const request = (): Promise<AxiosResponse> =>
      this.client.delete(path, { params });

    return await invokeRequest(request);
  }

  async post(path: string, data: JSONObject): Promise<unknown> {
    const request = (): Promise<AxiosResponse> => this.client.post(path, data);

    return await invokeRequest(request);
  }

  async put(path: string, data: JSONObject): Promise<unknown> {
    const request = (): Promise<AxiosResponse> => this.client.put(path, data);

    return await invokeRequest(request);
  }

  async patch(path: string, data: JSONObject): Promise<unknown> {
    const request = (): Promise<AxiosResponse> => this.client.patch(path, data);

    return await invokeRequest(request);
  }
}

export default new API();
