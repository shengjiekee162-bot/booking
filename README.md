# 餐厅预订与提前点餐系统 / Restaurant Booking & Pre-Order System

一个功能完整的餐厅预订和提前点餐系统，使用 PHP 和 MySQL 开发。

A complete restaurant booking and pre-order system built with PHP and MySQL.

---

## ⚡ 快速信息 / Quick Info

- 🕐 **营业时间 / Business Hours**: 10:00 AM - 10:00 PM | 预订时间 / Booking Hours: 11:00 AM - 9:45 PM (Last Call)
- 🪑 **可用桌子 / Available Tables**: 10张桌子，容量从2人到10人
- 🚫 **防重复预订 / Double Booking Prevention**: 2小时缓冲时间
- 📚 **历史记录 / History**: 支持筛选、搜索和分页查看
- 🔄 **实时验证 / Real-time Validation**: 前端+后端双重验证
- 👤 **可选登录 / Optional Login**: 支持登录会员和游客模式

---

## 功能特点 / Features

### 🔐 用户系统 / User System (NEW - 可选 Optional)
- ✅ **用户注册与登录** / User Registration & Login
  - 邮箱和密码注册
  - 安全密码哈希存储（bcrypt）
  - 自动表单填充
  - **完全可选 - 无需登录也能预订** / Completely optional - booking without login

- 👤 **会员功能** / Member Features
  - 自动填充个人信息（姓名、邮箱、电话）
  - 查看所有历史预订
  - 预订统计面板（总数、已确认、待处理、含订单）
  - 一键访问个人预订记录

- 👥 **游客模式** / Guest Mode
  - 无需注册即可预订
  - 通过预订ID查看预订详情
  - 完整的预订和点餐功能
  - 随时可以注册成为会员

### 客户端功能 / Customer Features
- ✅ **餐桌预订** / Table Booking
  - 选择日期、时间和人数
  - **选择指定桌子（桌号 1-10）**
  - **实时检查桌子可用性**
  - **防止同一时间段重复预订**
  - 显示桌子容量和描述信息
  - 填写特殊要求
  - 实时验证预订信息
  
- 🍜 **在线点餐** / Online Pre-Order
  - 浏览完整菜单（含中英文说明）
  - 按类别分类（主食、汤品、前菜、甜品、饮料）
  - 实时购物车显示
  - 自动计算总价

- 📋 **查看预订** / View Booking
  - 通过预订编号查询
  - 查看预订详情和订单明细
  - 实时状态更新

- 📚 **历史记录** / History Records (NEW)
  - 查看所有预订历史
  - 按状态、日期、桌号筛选
  - 搜索客户姓名或电话
  - 分页显示，每页 20 条记录
  - 显示详细预订信息和订单金额

### 管理后台 / Admin Features
- 📊 **统计面板** / Dashboard
  - 总预订数
  - 待确认预订数
  - 已确认预订数
  - 总订单数

- 🎫 **预订管理** / Booking Management
  - 查看所有预订
  - 更新预订状态（待确认/已确认/已取消/已完成）
  - 分配桌号
  - 查看客户信息

- 📦 **订单管理** / Order Management
  - 查看所有食品订单
  - 更新订单状态（待确认/已确认/准备中/已完成/已取消）
  - 查看订单明细和金额

## ⏰ 营业时间 / Business Hours

**餐厅营业时间：10:00 AM - 10:00 PM (22:00)**
**预订接受时间：11:00 AM - 9:45 PM (21:45 - Last Call)**

- 餐厅从早上10点开门到晚上10点关门
- 预订只接受从11:00 AM 到 9:45 PM (Last Call)
- 预订时间必须在 11:00 - 21:45 之间
- 最后点餐时间：21:45 (9:45 PM)
- 系统会自动验证选择的时间
- 前端和后端都有时间验证机制
- 早于 11:00 或晚于 21:45 的预订将被拒绝

**Restaurant Hours: 10:00 AM - 10:00 PM (22:00)**
**Booking Hours: 11:00 AM - 9:45 PM (21:45 - Last Call)**

- Restaurant opens at 10:00 AM and closes at 10:00 PM
- Bookings are only accepted from 11:00 AM to 9:45 PM (Last Call)
- Booking times must be between 11:00 AM - 9:45 PM
- Last call for orders: 9:45 PM (21:45)
- System automatically validates selected times
- Both frontend and backend validation in place
- Bookings before 11:00 AM or after 9:45 PM will be rejected

## 🪑 桌子管理 / Table Management

系统提供 **10 张桌子** 供顾客选择：

**System provides 10 tables for customer selection:**

| 桌号 Table | 容量 Capacity | 描述 Description |
|-----------|---------------|------------------|
| 1 | 2人 | 靠窗双人桌 / Window table for 2 |
| 2 | 2人 | 双人桌 / Table for 2 |
| 3 | 4人 | 四人桌 / Table for 4 |
| 4 | 4人 | 四人桌 / Table for 4 |
| 5 | 6人 | 六人桌 / Table for 6 |
| 6 | 6人 | 靠窗六人桌 / Window table for 6 |
| 7 | 8人 | 八人大桌 / Large table for 8 |
| 8 | 4人 | 四人桌 / Table for 4 |
| 9 | 2人 | 双人桌 / Table for 2 |
| 10 | 10人 | VIP包厢 / VIP room for 10 |

### 防止重复预订机制 / Prevent Double Booking

- ✅ **实时可用性检查**：选择日期、时间和桌子后自动检查
- ✅ **2小时缓冲时间**：同一桌子在2小时内不能重复预订
- ✅ **前端提示**：如果桌子已被预订，会显示红色警告
- ✅ **后端验证**：提交时再次验证，确保数据一致性
- ✅ **容量提示**：系统会显示桌子容量，帮助顾客选择合适的桌子

### Real-time Availability Check

- ✅ **Real-time availability check**: Automatically checks after selecting date, time and table
- ✅ **2-hour buffer**: Same table cannot be double-booked within 2 hours
- ✅ **Frontend alert**: Red warning displayed if table is already booked
- ✅ **Backend validation**: Re-validates on submission for data consistency
- ✅ **Capacity hint**: System shows table capacity to help customers choose

## 技术栈 / Tech Stack

- **后端** / Backend: PHP 7.4+
- **数据库** / Database: MySQL 5.7+
- **前端** / Frontend: HTML5, CSS3, JavaScript
- **服务器** / Server: Apache (XAMPP)

## 安装步骤 / Installation

### 1. 环境要求 / Requirements
- XAMPP (Apache + MySQL + PHP)
- 浏览器 / Web Browser

### 2. 安装 XAMPP
如果还没有安装 XAMPP，请从官网下载并安装：
https://www.apachefriends.org/

### 3. 部署项目 / Deploy Project

项目文件已经在正确的位置：
```
c:\xampp\htdocs\booking_jie\
```

### 4. 创建数据库 / Create Database

1. 启动 XAMPP 控制面板
2. 启动 Apache 和 MySQL 服务
3. 打开浏览器访问：http://localhost/phpmyadmin
4. 创建新数据库或导入 SQL 文件：
   - 点击 "Import" / "导入"
   - 选择 `database.sql` 文件
   - 点击 "Go" / "执行"

或者使用 SQL 标签页执行 `database.sql` 文件中的 SQL 语句。

### 5. 配置数据库连接 / Configure Database

文件 `config.php` 中的默认配置：
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'booking_jie');
```

如果您的 MySQL 设置不同，请修改这些值。

### 6. 访问系统 / Access System

启动 XAMPP 后，在浏览器中访问：

- **主页（预订）** / Home (Booking): http://localhost/booking_jie/
- **菜单（点餐）** / Menu (Order): http://localhost/booking_jie/menu.php
- **查看预订** / View Booking: http://localhost/booking_jie/view_booking.php
- **管理后台** / Admin Panel: http://localhost/booking_jie/admin.php
- **历史记录** / History: http://localhost/booking_jie/history.php
- **用户登录** / Login: http://localhost/booking_jie/login.php (NEW)
- **用户注册** / Register: http://localhost/booking_jie/register.php (NEW)
- **我的预订** / My Bookings: http://localhost/booking_jie/my_bookings.php (NEW - 需要登录)

## 使用流程 / Usage Flow

### 客户使用流程 / Customer Flow

#### 🎯 方式一：会员模式 (推荐 Recommended)

1. **注册账户** / Register
   - 访问 `register.php` 或点击导航栏的 "注册 Register"
   - 填写姓名、邮箱、电话和密码
   - 提交注册，自动跳转到登录页面

2. **登录** / Login
   - 访问 `login.php` 或点击导航栏的 "登录 Login"
   - 输入邮箱和密码
   - 登录成功后，所有页面顶部会显示欢迎信息

3. **预订餐桌** / Book a Table (登录后自动填充)
   - 访问首页 `index.php`
   - 系统自动填充姓名、邮箱、电话（不可修改）
   - 选择预订日期、时间和人数
   - **选择桌子（根据容量选择合适的桌子）**
   - **注意：营业时间为每天 11:00 AM - 9:45 PM (Last Call)**
   - 提交后获得预订编号，预订自动关联到您的账户

4. **查看我的预订** / My Bookings
   - 点击顶部的 "我的预订 My Bookings"
   - 查看所有您的预订历史
   - 查看统计数据（总预订、已确认、待处理、含订单）
   - 查看每笔预订的详细信息和订单金额

5. **提前点餐** / Pre-Order Food
   - 预订成功后点击"现在点餐"或访问 `menu.php`
   - 浏览菜单，选择菜品和数量
   - 查看购物车总计
   - 提交订单

#### 🎯 方式二：游客模式 (无需登录 No Login Required)

1. **直接预订餐桌** / Book a Table Directly
   - 访问首页 `index.php`
   - 手动填写姓名、电话、邮箱
   - 选择预订日期、时间、人数和桌子
   - **注意：营业时间为每天 11:00 AM - 9:45 PM (Last Call)**
   - **系统会自动检查桌子是否已被预订**
   - 提交后获得预订编号（请妥善保存）

2. **提前点餐** / Pre-Order Food
   - 预订成功后点击"现在点餐"或访问 `menu.php`
   - 浏览菜单，选择菜品和数量
   - 查看购物车总计
   - 提交订单

3. **查看预订** / View Booking
   - 访问首页 `index.php`
   - 填写姓名、电话、预订日期、时间和人数
   - **选择桌子（根据容量选择合适的桌子）**
   - **注意：营业时间为每天 11:00 AM - 9:45 PM (Last Call)**
   - **预订时间必须在营业时间内**
   - **系统会自动检查桌子是否已被预订**
   - 提交后获得预订编号

2. **提前点餐** / Pre-Order Food
   - 预订成功后点击"现在点餐"或访问 `menu.php`
   - 浏览菜单，选择菜品和数量
   - 查看购物车总计
   - 提交订单

3. **查看预订** / View Booking
   - 访问 `view_booking.php`
   - 输入预订编号查询
   - 查看预订详情和订单明细

4. **查看历史记录** / View History (NEW)
   - 访问 `history.php`
   - 浏览所有预订历史
   - 使用筛选功能查找特定预订
   - 支持按状态、日期、桌号筛选
   - 支持搜索客户姓名或电话

### 管理员使用流程 / Admin Flow

1. 访问 `admin.php` 进入管理后台
2. 查看统计数据
3. 在"预订管理"选项卡中：
   - 查看所有预订
   - 分配桌号
   - 更新预订状态
4. 在"订单管理"选项卡中：
   - 查看所有订单
   - 更新订单状态

## 数据库结构 / Database Structure

### 表 / Tables

1. **users** - 用户账户 (NEW - 可选)
   - id, email, password (hashed), name, phone, created_at, last_login
   - 支持用户注册和登录
   - 密码使用 bcrypt 加密存储

2. **customers** - 客户信息
   - id, name, phone, email, user_id (外键关联users表，可为NULL), created_at
   - user_id 为 NULL 表示游客预订

3. **tables** - 桌子信息
   - id, table_number, capacity, description, available, created_at

3. **bookings** - 预订信息
   - id, customer_id, booking_date, booking_time, number_of_guests
   - table_number, special_requests, status, created_at

4. **food_menu** - 菜单
   - id, name, description, category, price, image_url, available, created_at

5. **food_orders** - 食品订单
   - id, booking_id, total_amount, status, created_at

6. **order_items** - 订单明细
   - id, order_id, food_item_id, quantity, price

## 样例数据 / Sample Data

数据库包含 10 个样例菜品：

### 主食 / Main Course
- Nasi Lemak - RM 12.90
- Char Kway Teow - RM 15.50
- Hainanese Chicken Rice - RM 13.90

### 汤品 / Soup
- Tom Yam Soup - RM 8.90

### 前菜 / Appetizer
- Satay (10 sticks) - RM 12.00
- Spring Rolls (5 pcs) - RM 7.50

### 甜品 / Dessert
- Mango Sticky Rice - RM 8.50
- Ice Kacang - RM 6.50

### 饮料 / Beverage
- Teh Tarik - RM 3.50
- Fresh Coconut Water - RM 5.00

## 状态说明 / Status Explanation

### 预订状态 / Booking Status
- **pending** - 待确认 / Pending confirmation
- **confirmed** - 已确认 / Confirmed
- **cancelled** - 已取消 / Cancelled
- **completed** - 已完成 / Completed

### 订单状态 / Order Status
- **pending** - 待确认 / Pending confirmation
- **confirmed** - 已确认 / Confirmed
- **preparing** - 准备中 / Preparing
- **completed** - 已完成 / Completed
- **cancelled** - 已取消 / Cancelled

## 故障排除 / Troubleshooting

### 1. 无法连接数据库 / Cannot connect to database
- 确保 XAMPP 中的 MySQL 服务已启动
- 检查 `config.php` 中的数据库配置
- 确认数据库 `booking_jie` 已创建

### 2. 页面显示空白 / Blank page
- 检查 Apache 错误日志：`c:\xampp\apache\logs\error.log`
- 确保 PHP 扩展已启用（mysqli）
- 检查文件权限

### 3. 样式不显示 / Styles not showing
- 确保 `style.css` 文件存在
- 清除浏览器缓存
- 检查文件路径是否正确

## 文件结构 / File Structure

```
booking_jie/
├── config.php                    # 数据库配置 / Database configuration
├── database.sql                  # 数据库架构 / Database schema
├── index.php                     # 主页（预订）/ Home page (Booking)
├── menu.php                      # 菜单（点餐）/ Menu page (Order)
├── view_booking.php              # 查看预订 / View booking
├── admin.php                     # 管理后台 / Admin panel
├── history.php                   # 历史记录 / History records (NEW)
├── process_booking.php           # 处理预订 / Process booking
├── process_order.php             # 处理订单 / Process order
├── check_table_availability.php  # 检查桌子可用性 / Check table availability (NEW)
├── style.css                     # 样式表 / Stylesheet
└── README.md                     # 说明文档 / Documentation
```

## 未来改进 / Future Improvements

- [ ] 添加用户认证和权限管理
- [ ] 邮件通知功能
- [ ] 短信提醒功能
- [ ] 在线支付集成
- [ ] 多语言切换
- [ ] 餐桌可视化布局
- [x] ~~预订时段冲突检测~~（已完成 / Completed）
- [ ] 数据导出功能（Excel/PDF）
- [ ] 动态营业时间设置
- [ ] 菜品图片上传
- [x] ~~历史记录查看~~（已完成 / Completed）
- [x] ~~桌子选择功能~~（已完成 / Completed）

## 最新更新 / Latest Updates

### v1.1.0 (2026-02-05)
- ✅ 更新营业时间：Last Call 时间改为 21:45 (9:45 PM)
- ✅ 添加桌子管理系统（10张桌子，不同容量）
- ✅ 实现防止重复预订功能（2小时缓冲时间）
- ✅ 添加历史记录页面，支持筛选和搜索
- ✅ 实时桌子可用性检查（AJAX）
- ✅ 分页显示历史记录（每页20条）

## 许可证 / License

MIT License

## 支持 / Support

如有问题或建议，请联系开发团队。

For questions or suggestions, please contact the development team.

---

**开发时间** / Developed: 2026
**版本** / Version: 1.0.0
#   b o o k i n g  
 