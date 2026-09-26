import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import {
  taoBodyDeXuat,
  taoDeXuatKeHoach,
  taiDanhSachDeXuat,
} from './de_xuat.api.js'

const KHOA = '9c87e6ae-69e1-4a15-8330-9848fe48c7d2'

function taoDraft() {
  return {
    change_type: 'DIEU_CHINH',
    title: 'Tăng dần sức mạnh',
    explanation: 'Điều chỉnh số hiệp theo tiến độ hiện tại.',
    effective_from: '2026-10-02',
    source: 'CLIENT_MUST_NOT_SEND',
    assignment_id: 999,
    status: 'DA_AP_DUNG',
    plan: {
      name: 'Sức mạnh 1',
      goal: 'Tăng sức mạnh',
      template_id: 8,
      base_plan_id: 44,
      days: [{
        order: 1,
        weekday: 2,
        name: 'Ngày thân trên',
        estimated_minutes: 60,
        secret: 'omit',
        exercises: [{
          exercise_id: 12,
          order: 1,
          target_sets: 4,
          min_reps: 6,
          max_reps: 8,
          target_weight_kg: 20,
          rest_seconds: 90,
          notes: null,
          content_hash: 'omit',
        }],
      }],
    },
  }
}

describe('de_xuat.api FE6', () => {
  beforeEach(() => vi.restoreAllMocks())

  it('uses the current-member proposal collection and sends only allow-listed fields', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: [{ id: 3 }] })
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue({ data: { id: 4, status: 'CHO_XAC_NHAN' } })

    await expect(taiDanhSachDeXuat(7)).resolves.toEqual([{ id: 3 }])
    await taoDeXuatKeHoach(7, taoDraft(), KHOA)

    expect(get).toHaveBeenCalledWith('/pt/members/7/proposals')
    expect(post).toHaveBeenCalledWith('/pt/members/7/proposals', {
      change_type: 'DIEU_CHINH',
      title: 'Tăng dần sức mạnh',
      explanation: 'Điều chỉnh số hiệp theo tiến độ hiện tại.',
      effective_from: '2026-10-02',
      plan: {
        name: 'Sức mạnh 1',
        goal: 'Tăng sức mạnh',
        template_id: 8,
        days: [{
          order: 1,
          weekday: 2,
          name: 'Ngày thân trên',
          estimated_minutes: 60,
          exercises: [{
            exercise_id: 12,
            order: 1,
            target_sets: 4,
            min_reps: 6,
            max_reps: 8,
            rest_seconds: 90,
            target_weight_kg: 20,
            notes: null,
          }],
        }],
      },
    }, { headers: { 'Idempotency-Key': KHOA } })
  })

  it('builds a new allow-listed body without mutating the draft or transmitting server-owned fields', () => {
    const draft = taoDraft()
    const body = taoBodyDeXuat(draft)

    expect(draft.source).toBe('CLIENT_MUST_NOT_SEND')
    expect(body).not.toHaveProperty('source')
    expect(body).not.toHaveProperty('assignment_id')
    expect(body).not.toHaveProperty('status')
    expect(body.plan).not.toHaveProperty('base_plan_id')
    expect(body.plan.days[0]).not.toHaveProperty('secret')
    expect(body.plan.days[0].exercises[0]).not.toHaveProperty('content_hash')
  })

  it('rejects malformed nested values and non-UUIDv4 keys before POST', async () => {
    const post = vi.spyOn(ketNoiApi, 'post')
    const draft = taoDraft()
    draft.plan.days[0].exercises[0].max_reps = 4

    await expect(taoDeXuatKeHoach(7, draft, KHOA)).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taoDeXuatKeHoach(7, taoDraft(), 'not-a-uuid')).rejects.toMatchObject({ httpStatus: 422 })
    expect(post).not.toHaveBeenCalled()
  })
})
