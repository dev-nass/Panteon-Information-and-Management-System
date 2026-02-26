const Ziggy = {"url":"http:\/\/localhost:8000","port":8000,"defaults":{},"routes":{"boost.browser-logs":{"uri":"_boost\/browser-logs","methods":["POST"]},"clerk.dashboard":{"uri":"clerk\/dashboard","methods":["GET","HEAD"]},"clerk.map.index":{"uri":"clerk\/map","methods":["GET","HEAD"]},"clerk.deceased_records.index":{"uri":"clerk\/deceased-records","methods":["GET","HEAD"]},"clerk.deceased_records.show":{"uri":"clerk\/deceased-records\/{deceased_record}","methods":["GET","HEAD"],"parameters":["deceased_record"],"bindings":{"deceased_record":"id"}},"storage.local":{"uri":"storage\/{path}","methods":["GET","HEAD"],"wheres":{"path":".*"},"parameters":["path"]}}};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
