import apiClient from "./client";

import type {
  Enrollment,
  EnrollmentListResponse,
  EnrollmentQueryParams,
  EnrollmentStatus,
  Semester,
} from "@/types/enrollment";

export interface CreateEnrollmentPayload {
  student: {
    nim: string;
    name: string;
    email: string;
  };

  course: {
    code: string;
    name: string;
    credits: number;
  };

  academic_year: string;
  semester: Semester;
  status: EnrollmentStatus;
}

export interface UpdateEnrollmentPayload {
  academic_year: string;
  semester: Semester;
  status: EnrollmentStatus;
}

export interface EnrollmentResourceResponse {
  message: string;
  data: Enrollment;
}

export interface EnrollmentDeleteResponse {
  message: string;
}

/* ==========================================================
   GET ENROLLMENTS
========================================================== */

export async function getEnrollments(
  params: EnrollmentQueryParams,
): Promise<EnrollmentListResponse> {
  const {
    page,
    pageSize,
    filters,
    sorts,
    ...legacyParams
  } = params;

  const requestParams = {
    ...legacyParams,

    ...(page !== undefined
      ? {
          page,
        }
      : {}),

    ...(pageSize !== undefined
      ? {
          page_size: pageSize,
        }
      : {}),

    ...(filters !== undefined
      ? {
          filters: JSON.stringify(filters),
        }
      : {}),

    ...(sorts !== undefined
      ? {
          sorts: JSON.stringify(sorts),
        }
      : {}),
  };

  const response =
    await apiClient.get<EnrollmentListResponse>(
      "/enrollments",
      {
        params: requestParams,
      },
    );

  return response.data;
}

/* ==========================================================
   CREATE ENROLLMENT
========================================================== */

export async function createEnrollment(
  payload: CreateEnrollmentPayload,
): Promise<EnrollmentResourceResponse> {
  const response =
    await apiClient.post<EnrollmentResourceResponse>(
      "/enrollments",
      payload,
    );

  return response.data;
}

/* ==========================================================
   UPDATE ENROLLMENT
========================================================== */

export async function updateEnrollment(
  id: number,
  payload: UpdateEnrollmentPayload,
): Promise<EnrollmentResourceResponse> {
  const response =
    await apiClient.put<EnrollmentResourceResponse>(
      `/enrollments/${id}`,
      payload,
    );

  return response.data;
}

/* ==========================================================
   DELETE ENROLLMENT
========================================================== */

export async function deleteEnrollment(
  id: number,
): Promise<EnrollmentDeleteResponse> {
  const response =
    await apiClient.delete<EnrollmentDeleteResponse>(
      `/enrollments/${id}`,
    );

  return response.data;
}

/* ==========================================================
   EXPORT ALL ENROLLMENTS
========================================================== */

export async function exportEnrollments(
  params: EnrollmentQueryParams,
): Promise<Blob> {
  /*
   * Export tidak boleh menggunakan pagination.
   *
   * page dan pageSize sengaja dibuang.
   */
  const {
    page: _page,
    pageSize: _pageSize,
    filters,
    sorts,
    ...legacyParams
  } = params;

  /*
   * Build query parameters untuk export.
   */
  const requestParams = {
    ...legacyParams,

    ...(filters !== undefined
      ? {
          filters: JSON.stringify(filters),
        }
      : {}),

    ...(sorts !== undefined
      ? {
          sorts: JSON.stringify(sorts),
        }
      : {}),
  };

  /*
   * IMPORTANT:
   *
   * Client global memiliki timeout 30 detik.
   * Export 5 juta baris tidak boleh dibatasi
   * oleh timeout request list biasa.
   *
   * timeout: 0 = no Axios timeout.
   *
   * Ini HANYA berlaku untuk request export.
   * Request list/CRUD tetap 30 detik.
   */
  const response =
    await apiClient.get<Blob>(
      "/enrollments/export",
      {
        params: requestParams,

        responseType: "blob",

        timeout: 0,
      },
    );

  return response.data;
}