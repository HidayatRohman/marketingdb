export type HttpMethod = 'get' | 'post' | 'put' | 'delete' | 'head'

export interface RouteDefinition<M extends HttpMethod> {
  url: string
  method: M
}

export interface RouteFormDefinition<M extends HttpMethod> {
  action: string
  method: M
}

export type RouteQueryOptions = {
  query?: Record<string, string | number | boolean | null | undefined>
  mergeQuery?: Record<string, string | number | boolean | null | undefined>
}

export const queryParams = (options?: RouteQueryOptions): string => {
  const params = options?.mergeQuery ?? options?.query
  if (!params) return ''
  const search = new URLSearchParams()
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === null) continue
    search.append(key, String(value))
  }
  const qs = search.toString()
  return qs ? `?${qs}` : ''
}

export const applyUrlDefaults = (url: string, options?: RouteQueryOptions): string => {
  return url + queryParams(options)
}