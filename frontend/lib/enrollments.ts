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

export async function getEnrollments(
  params: EnrollmentQueryParams,
): Promise<EnrollmentListResponse> {
  const response =
    await apiClient.get<EnrollmentListResponse>(
      "/enrollments",
      {
        params,
      },
    );

  return response.data;
}

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

export async function deleteEnrollment(
  id: number,
): Promise<EnrollmentDeleteResponse> {
  const response =
    await apiClient.delete<EnrollmentDeleteResponse>(
      `/enrollments/${id}`,
    );

  return response.data;
}