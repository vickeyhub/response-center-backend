import request from '@/utils/request';

export function fetchList(query) {
  return request({
    url: `/api/clinical_services${query}`,
    method: 'get',
  });
}

export function fetch(id) {
  return request({
    url: `/api/clinical_services/${id}`,
    method: 'get',
  });
}

export function deleteRecord(id) {
  return request({
    url: `/api/clinical_services/${id}`,
    method: 'delete',
  });
}


export function deleteMultiple(data) {
  return request({
    url: `/api/remove-clinical_services`,
    method: 'post',
    data
  });
}

export function create(data) {
  return request({
    url: '/api/clinical_services',
    method: 'post',
    data,
  });
}

export function update(data, id) {
  return request({
    url: `/api/clinical_services/${id}`,
    method: 'put',
    data,
  });
}
