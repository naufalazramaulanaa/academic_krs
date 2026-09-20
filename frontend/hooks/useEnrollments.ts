"use client";

import {
  useCallback,
  useEffect,
  useRef,
  useState,
} from "react";

import { getEnrollments } from "@/lib/enrollments";
import { getApiErrorMessage } from "@/lib/error";

import type {
  EnrollmentListResponse,
  EnrollmentQueryParams,
} from "@/types/enrollment";

interface UseEnrollmentsResult {
  data: EnrollmentListResponse["data"];
  meta: EnrollmentListResponse["meta"] | null;
  links: EnrollmentListResponse["links"] | null;

  loading: boolean;
  error: string | null;

  refetch: () => void;
}

export function useEnrollments(
  params: EnrollmentQueryParams,
): UseEnrollmentsResult {
  const [data, setData] = useState<
    EnrollmentListResponse["data"]
  >([]);

  const [meta, setMeta] = useState<
    EnrollmentListResponse["meta"] | null
  >(null);

  const [links, setLinks] = useState<
    EnrollmentListResponse["links"] | null
  >(null);

  const [loading, setLoading] = useState(true);

  const [error, setError] = useState<string | null>(
    null,
  );

  const [refreshKey, setRefreshKey] = useState(0);

  const requestIdRef = useRef(0);

  const refetch = useCallback(() => {
    setRefreshKey((current) => current + 1);
  }, []);

  useEffect(() => {
    let cancelled = false;

    const requestId = ++requestIdRef.current;

    async function fetchEnrollments() {
      setLoading(true);
      setError(null);

      try {
        const response = await getEnrollments(params);

        if (
          cancelled ||
          requestId !== requestIdRef.current
        ) {
          return;
        }

        setData(response.data);
        setMeta(response.meta);
        setLinks(response.links);
      } catch (err) {
        if (
          cancelled ||
          requestId !== requestIdRef.current
        ) {
          return;
        }

        setError(
          getApiErrorMessage(
            err,
            "Gagal memuat data enrollment. Silakan coba lagi.",
          ),
        );

        setData([]);
        setMeta(null);
        setLinks(null);
      } finally {
        if (
          !cancelled &&
          requestId === requestIdRef.current
        ) {
          setLoading(false);
        }
      }
    }

    fetchEnrollments();

    return () => {
      cancelled = true;
    };
  }, [params, refreshKey]);

  return {
    data,
    meta,
    links,
    loading,
    error,
    refetch,
  };
}